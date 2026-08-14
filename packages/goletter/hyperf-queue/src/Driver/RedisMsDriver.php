<?php

declare(strict_types=1);

namespace Goletter\Queue\Driver;

use Goletter\Queue\Contract\MsDriverInterface;
use Hyperf\AsyncQueue\Driver\ChannelConfig;
use Hyperf\AsyncQueue\Driver\Driver;
use Hyperf\AsyncQueue\Exception\InvalidQueueException;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\AsyncQueue\JobMessage;
use Hyperf\AsyncQueue\MessageInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Process\ProcessManager;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Psr\Container\ContainerInterface;
use Throwable;

use function Hyperf\Support\make;

/**
 * Redis async-queue driver with millisecond delay precision.
 *
 * Compatible with hyperf/async-queue DriverFactory and ConsumerProcess.
 * On this driver, DriverInterface::push($job, $delay) treats $delay as **milliseconds**
 * (so QueueService::push($job, 'ms', 10) means 10ms). Official RedisDriver still uses seconds.
 * Do NOT mix this driver with Hyperf\AsyncQueue\Driver\RedisDriver on the same channel.
 */
class RedisMsDriver extends Driver implements MsDriverInterface
{
    protected RedisProxy $redis;

    protected ChannelConfig $channel;

    /**
     * BRPOP timeout in seconds.
     */
    protected int $timeout;

    /**
     * Retry delay in milliseconds.
     *
     * @var array<int, int>|int
     */
    protected array|int $retryMilliseconds;

    /**
     * Handle timeout in seconds (converted to ms when writing reserved score).
     */
    protected int $handleTimeout;

    /**
     * Mover coroutine interval in microseconds.
     */
    protected int $moveIntervalUs;

    protected int $moveBatch;

    protected bool $moverStarted = false;

    public function __construct(ContainerInterface $container, $config)
    {
        parent::__construct($container, $config);

        $channel = $config['channel'] ?? 'queue';
        $this->redis = $container->get(RedisFactory::class)->get($config['redis']['pool'] ?? 'default');
        $this->timeout = (int) ($config['timeout'] ?? 5);
        $this->handleTimeout = (int) ($config['handle_timeout'] ?? 10);
        $this->moveBatch = max(1, (int) ($config['move_batch'] ?? 200));
        $this->moveIntervalUs = max(1000, (int) ($config['move_interval_ms'] ?? 5) * 1000);
        $this->retryMilliseconds = $this->resolveRetryMilliseconds($config);
        $this->channel = make(ChannelConfig::class, ['channel' => $channel]);
    }

    /**
     * @param int $delay delay in milliseconds (unlike official RedisDriver which uses seconds)
     */
    public function push(JobInterface $job, int $delay = 0): bool
    {
        return $this->pushMs($job, max(0, $delay));
    }

    public function pushMs(JobInterface $job, int $delayMs = 0): bool
    {
        $message = make(JobMessage::class, [$job]);
        $data = $this->packer->pack($message);

        if ($delayMs <= 0) {
            return (bool) $this->redis->lPush($this->channel->getWaiting(), $data);
        }

        $score = $this->nowMs() + $delayMs;

        return (bool) $this->redis->zAdd($this->channel->getDelayed(), $score, $data);
    }

    public function delete(JobInterface $job): bool
    {
        $message = make(JobMessage::class, [$job]);
        $data = $this->packer->pack($message);

        return (bool) $this->redis->zRem($this->channel->getDelayed(), $data);
    }

    public function pop(): array
    {
        $this->ensureMoverStarted();
        $this->moveDue($this->channel->getDelayed(), $this->channel->getWaiting());
        $this->moveDue($this->channel->getReserved(), $this->channel->getTimeout());

        $res = $this->redis->brPop($this->channel->getWaiting(), $this->timeout);
        if (! isset($res[1])) {
            return [false, null];
        }

        $data = $res[1];
        $this->redis->zAdd(
            $this->channel->getReserved(),
            $this->nowMs() + ($this->handleTimeout * 1000),
            $data
        );

        $message = $this->packer->unpack($data);
        if (! $message) {
            return [false, null];
        }

        return [$data, $message];
    }

    public function ack(mixed $data): bool
    {
        return $this->remove($data);
    }

    public function fail(mixed $data): bool
    {
        if ($this->remove($data)) {
            return (bool) $this->redis->lPush($this->channel->getFailed(), (string) $data);
        }

        return false;
    }

    public function reload(?string $queue = null): int
    {
        $channel = $this->channel->getFailed();
        if ($queue) {
            if (! in_array($queue, ['timeout', 'failed'], true)) {
                throw new InvalidQueueException(sprintf('Queue %s is not supported.', $queue));
            }
            $channel = $this->channel->get($queue);
        }

        $num = 0;
        while ($this->redis->rpoplpush($channel, $this->channel->getWaiting())) {
            ++$num;
        }

        return $num;
    }

    public function flush(?string $queue = null): bool
    {
        $channel = $this->channel->getFailed();
        if ($queue) {
            $channel = $this->channel->get($queue);
        }

        return (bool) $this->redis->del($channel);
    }

    public function info(): array
    {
        return [
            'waiting' => (int) $this->redis->lLen($this->channel->getWaiting()),
            'delayed' => (int) $this->redis->zCard($this->channel->getDelayed()),
            'failed' => (int) $this->redis->lLen($this->channel->getFailed()),
            'timeout' => (int) $this->redis->lLen($this->channel->getTimeout()),
            'reserved' => (int) $this->redis->zCard($this->channel->getReserved()),
        ];
    }

    public function consume(): void
    {
        $this->ensureMoverStarted();
        parent::consume();
    }

    protected function retry(MessageInterface $message): bool
    {
        $data = $this->packer->pack($message);
        $delayMs = $this->getRetryMilliseconds($message->getAttempts());
        $score = $this->nowMs() + $delayMs;

        return (bool) $this->redis->zAdd($this->channel->getDelayed(), $score, $data);
    }

    protected function remove(mixed $data): bool
    {
        return (bool) $this->redis->zRem($this->channel->getReserved(), (string) $data);
    }

    protected function ensureMoverStarted(): void
    {
        if ($this->moverStarted) {
            return;
        }
        $this->moverStarted = true;

        Coroutine::create(function () {
            while (ProcessManager::isRunning()) {
                try {
                    $this->moveDue($this->channel->getDelayed(), $this->channel->getWaiting());
                    $this->moveDue($this->channel->getReserved(), $this->channel->getTimeout());
                } catch (Throwable $exception) {
                    if ($this->container->has(StdoutLoggerInterface::class)) {
                        $this->container->get(StdoutLoggerInterface::class)->error(
                            sprintf('[goletter-queue] move due failed: %s', $exception)
                        );
                    }
                }
                usleep($this->moveIntervalUs);
            }
        });
    }

    protected function moveDue(string $from, string $to): int
    {
        $result = $this->redis->eval(
            LuaScripts::moveDue(),
            [$from, $to, (string) $this->nowMs(), (string) $this->moveBatch],
            2
        );

        return (int) $result;
    }

    protected function nowMs(): int
    {
        return (int) floor(microtime(true) * 1000);
    }

    protected function getRetryMilliseconds(int $attempts): int
    {
        if (! is_array($this->retryMilliseconds)) {
            return max(0, (int) $this->retryMilliseconds);
        }

        if ($this->retryMilliseconds === []) {
            return 1000;
        }

        return (int) ($this->retryMilliseconds[$attempts - 1] ?? end($this->retryMilliseconds));
    }

    /**
     * @param array<string, mixed> $config
     * @return array<int, int>|int
     */
    protected function resolveRetryMilliseconds(array $config): array|int
    {
        if (isset($config['retry_milliseconds'])) {
            return $config['retry_milliseconds'];
        }

        $retrySeconds = $config['retry_seconds'] ?? 10;
        if (is_array($retrySeconds)) {
            return array_map(static fn ($s) => (int) $s * 1000, $retrySeconds);
        }

        return (int) $retrySeconds * 1000;
    }
}
