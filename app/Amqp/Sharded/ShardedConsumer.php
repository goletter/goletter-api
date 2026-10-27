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

namespace App\Amqp\Sharded;

use Hyperf\Amqp\Builder\QueueBuilder;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use InvalidArgumentException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPAbstractCollection;
use PhpAmqpLib\Wire\AMQPTable;
use Throwable;

use function Goletter\Utils\logging;

/**
 * 分片消费者：quorum + 单活跃消费者 + prefetch=1。
 *
 * 子类实现 queueClass() 和 handle()。
 * 不要加 #[Consumer]，由 ShardedConsumeProcess 按分片拉起。
 */
abstract class ShardedConsumer extends ConsumerMessage
{
    protected string|Type $type = Type::TOPIC;

    protected ?array $qos = [
        'prefetch_size' => 0,
        'prefetch_count' => 1,
        'global' => false,
    ];

    public function __construct(protected int $shard)
    {
        $queueClass = static::queueClass();
        if (! is_a($queueClass, ShardedQueue::class, true)) {
            throw new InvalidArgumentException($queueClass . ' 必须继承 ' . ShardedQueue::class);
        }

        $this->setExchange($queueClass::exchange());
        $this->setRoutingKey($queueClass::routingKey($this->shard));
        $this->setQueue($queueClass::queue($this->shard));
    }

    public function getShard(): int
    {
        return $this->shard;
    }

    /**
     * 业务处理放在 handle()。
     * - 成功 ACK：按 intervalMs 间隔后再确认
     * - handle 抛异常：按 attempts / maxAttempts 延迟重投；超限 DROP
     * - handle 主动返回 DROP：不重试
     * @param mixed $data
     */
    final public function consumeMessage($data, AMQPMessage $message): Result
    {
        $queueClass = static::queueClass();
        $payload = is_array($data) ? $data : [];

        try {
            $result = $this->handle($data, $message);
        } catch (Throwable $e) {
            return $this->retryOrDrop($payload, $e);
        }

        // prefetch=1：ACK 前等待，同组分片下一条才会被投递
        $intervalMs = max(0, $queueClass::intervalMs());
        if ($intervalMs > 0 && $result === Result::ACK) {
            usleep($intervalMs * 1000);
        }

        return $result;
    }

    /**
     * 失败重试：payload.attempts 计数，未超限则延迟后 produce 新消息并 ACK 当前条。
     */
    protected function retryOrDrop(array $payload, Throwable $e): Result
    {
        $queueClass = static::queueClass();
        $attempts = (int) ($payload['attempts'] ?? 0) + 1;
        $maxAttempts = max(1, $queueClass::maxAttempts());

        logging([
            'event' => 'retry',
            'shard' => $this->getShard(),
            'key' => $queueClass::serialKey($payload),
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'error' => $e->getMessage(),
            ...$payload,
        ], '分片队列重试', 'queue');

        if ($attempts >= $maxAttempts) {
            logging([
                'event' => 'retry_exhausted',
                'shard' => $this->getShard(),
                'key' => $queueClass::serialKey($payload),
                'attempts' => $attempts,
                'error' => $e->getMessage(),
            ], '分片队列重试耗尽', 'queue');

            return Result::DROP;
        }

        $delayMs = max(0, $queueClass::retryDelayMs());
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $payload['attempts'] = $attempts;
        $producerClass = $queueClass::producerClass();
        if (! is_a($producerClass, ShardedProducer::class, true)) {
            throw new InvalidArgumentException($producerClass . ' 必须继承 ' . ShardedProducer::class);
        }
        produce(new $producerClass($payload));

        return Result::ACK;
    }

    public function getQueueBuilder(): QueueBuilder
    {
        $arguments = new AMQPTable();
        $arguments->set('x-queue-type', 'quorum');
        // 多实例部署时，同一分片只有一个消费者在干活
        $arguments->set('x-single-active-consumer', true, AMQPAbstractCollection::T_BOOL);

        return (new QueueBuilder())
            ->setQueue($this->getQueue())
            ->setArguments($arguments);
    }

    abstract protected function handle(mixed $data, AMQPMessage $message): Result;

    /**
     * @return class-string<ShardedQueue>
     */
    abstract protected static function queueClass(): string;
}
