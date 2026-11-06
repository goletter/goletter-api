<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Listener;

use Goletter\Observability\Log\Channels;
use Goletter\Amqp\Contract\QueueRegistryInterface;
use Goletter\Amqp\Sharded\ShardedQueue;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\Logger\LoggerFactory;
use InvalidArgumentException;
use PhpAmqpLib\Exception\AMQPConnectionClosedException;
use PhpAmqpLib\Exception\AMQPIOException;
use PhpAmqpLib\Exception\AMQPSocketException;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use Psr\Log\LoggerInterface;
use Throwable;

use function Hyperf\Support\env;
use function Swoole\Coroutine\run;

/**
 * 主进程 fork 前声明分片队列拓扑（DLX/DLQ + 全部分片）。
 *
 * 消费者进程只会声明自己消费的队列；此处一次性建好死信与分片，避免发版窗口内
 * 消息进 DLX 却因留证队列不存在被 broker 丢弃。
 *
 * QUEUE_DECLARE_ON_BOOT 默认 false；为 true 时在 fork 前声明，改由部署脚本亦可。
 * broker 不可达：打日志告警，不阻断 HTTP 启动；其它声明错误 fail-fast。
 *
 * BeforeMainServerStart 不在协程内，而 hyperf/amqp 的 SwooleIO 必须在协程中连接，
 * 因此声明逻辑包在 Coroutine\run 里执行。
 */
#[Listener]
class QueueTopologyListener implements ListenerInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private QueueRegistryInterface $registry,
        LoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->get(Channels::QUEUE);
    }

    public function listen(): array
    {
        return [BeforeMainServerStart::class];
    }

    public function process(object $event): void
    {
        // 默认关闭：远程 Broker 连不上会拖慢整个 start；拓扑改由部署脚本或首次消费时声明
        if (! filter_var(env('QUEUE_DECLARE_ON_BOOT', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $caught = null;

        // SwooleIO::connect 要求协程环境；主进程启动阶段用 run() 临时开调度器
        run(function () use (&$caught): void {
            try {
                $this->declareTopology();
            } catch (Throwable $exception) {
                $caught = $exception;
            }
        });

        if ($caught instanceof Throwable) {
            throw $caught;
        }
    }

    private function declareTopology(): void
    {
        $classes = $this->registry->classes();
        $shardTotal = 0;

        try {
            foreach ($classes as $queueClass) {
                if (! is_a($queueClass, ShardedQueue::class, true)) {
                    throw new InvalidArgumentException($queueClass . ' 必须继承 ' . ShardedQueue::class);
                }

                for ($shard = 0, $n = $queueClass::shards(); $shard < $n; ++$shard) {
                    $queueClass::declareShard($shard);
                    ++$shardTotal;
                }
            }
        } catch (Throwable $exception) {
            $context = [
                'error_type' => $exception::class,
                'error_message' => $exception->getMessage(),
                'queue_count' => count($classes),
            ];

            if ($this->isBrokerUnavailable($exception)) {
                $this->logger->warning('queue_topology_broker_unavailable', $context);
                fwrite(STDERR, sprintf(
                    "[queue] broker 不可达，跳过拓扑声明：%s\n",
                    $exception->getMessage()
                ));

                return;
            }

            $this->logger->error('queue_topology_declare_failed', $context);
            fwrite(STDERR, sprintf(
                "[queue] 拓扑声明失败，服务拒绝启动：%s\n",
                $exception->getMessage()
            ));

            throw $exception;
        }

        $this->logger->info('queue_topology_declared', [
            'queue_count' => count($classes),
            'shard_total' => $shardTotal,
        ]);
    }

    private function isBrokerUnavailable(Throwable $exception): bool
    {
        if ($exception instanceof AMQPIOException
            || $exception instanceof AMQPTimeoutException
            || $exception instanceof AMQPConnectionClosedException
            || $exception instanceof AMQPSocketException
        ) {
            return true;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'connection refused')
            || str_contains($message, 'timed out')
            || str_contains($message, 'connection reset')
            || str_contains($message, 'broken pipe');
    }
}
