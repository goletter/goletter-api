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
 * 通用分片基类。租户+业务串行场景可继承 TenantBusinessShardedQueue，
 * 只需声明 EXCHANGE 与 consumerClass() / producerClass()。
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

    /**
     * @return class-string<ShardedProducer>
     */
    abstract public static function producerClass(): string;

    public static function shards(): int
    {
        return 16;
    }

    /**
     * 同组分片内，本条 ACK 前等待的毫秒数（下一条才会被投递）。
     * 0 = 无间隔。只影响同一分片串行节奏，不同分片互不影响。
     */
    public static function intervalMs(): int
    {
        return 0;
    }

    /**
     * 失败自动重试的最大执行次数（含首次）。超过后 DROP（有 DLX 则进死信）。
     */
    public static function maxAttempts(): int
    {
        return 3;
    }

    /**
     * 失败后重投前等待的毫秒数（占着当前分片，同组后续消息也会等）。
     */
    public static function retryDelayMs(): int
    {
        return 2000;
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
