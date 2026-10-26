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
use InvalidArgumentException;
use PhpAmqpLib\Wire\AMQPAbstractCollection;
use PhpAmqpLib\Wire\AMQPTable;

/**
 * 分片消费者：quorum + 单活跃消费者 + prefetch=1。
 *
 * 子类实现 queueClass() 和 consumeMessage()。
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

    /**
     * @return class-string<ShardedQueue>
     */
    abstract protected static function queueClass(): string;
}
