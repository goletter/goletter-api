<?php

declare(strict_types=1);

namespace App\Amqp\Sharded;

use Hyperf\Amqp\ConnectionFactory;
use Hyperf\Amqp\Consumer;
use InvalidArgumentException;

use function Goletter\Utils\di;

/**
 * 按业务串行键分片：同一 key 进同一队列串行，不同 key 可并行。
 *
 * 子类只需实现 exchange() / serialKey() / consumerClass()。
 */
abstract class ShardedQueue
{
    abstract public static function exchange(): string;

    /**
     * 决定串行范围的业务键，例如 tenant_id:busine_id。
     */
    abstract public static function serialKey(array $data): string;

    /**
     * @return class-string<ShardedConsumer>
     */
    abstract public static function consumerClass(): string;

    public static function shards(): int
    {
        return 16;
    }

    public static function shard(array $data): int
    {
        $hash = (int) sprintf('%u', crc32(static::serialKey($data)));

        return $hash % static::shards();
    }

    public static function queue(int $shard): string
    {
        return static::exchange() . '.' . $shard;
    }

    public static function routingKey(int $shard): string
    {
        return 'shard.' . $shard;
    }

    /**
     * 发送前声明并绑定分片队列。否则交换机上没有匹配队列时，消息会被直接丢弃。
     */
    public static function declareShard(int $shard): void
    {
        $consumerClass = static::consumerClass();
        if (! is_a($consumerClass, ShardedConsumer::class, true)) {
            throw new InvalidArgumentException($consumerClass . ' 必须继承 ' . ShardedConsumer::class);
        }

        /** @var ShardedConsumer $message */
        $message = new $consumerClass($shard);
        $factory = di()->get(ConnectionFactory::class);
        $connection = $factory->getConnection($message->getPoolName());
        $channel = $connection->getChannel();
        try {
            di()->get(Consumer::class)->declare($message, $channel);
        } finally {
            $connection->releaseChannel($channel);
        }
    }
}
