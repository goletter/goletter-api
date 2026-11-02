<?php

declare(strict_types=1);

namespace App\Amqp\Queue;

use App\Amqp\Consumer\AccountInsightConsumer;
use App\Amqp\Producer\AccountInsightProducer;
use Goletter\Amqp\Sharded\TenantBusinessShardedQueue;

/**
 * accountInsight：同一 tenant_id + code（广告账户）串行，不同账户可并行。
 * 勿用 tenant+busine：同一 BM 下多账户会挤进同一分片，吞吐被锁死。
 */
final class AccountInsightQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'accountInsight';

    public static function consumerClass(): string
    {
        return AccountInsightConsumer::class;
    }

    public static function producerClass(): string
    {
        return AccountInsightProducer::class;
    }

    public static function serialFields(): array
    {
        return ['tenant_id', 'code'];
    }

    /** 失败不重试：首次失败即 DROP 进死信 */
    public static function maxAttempts(): int
    {
        return 1;
    }

    public static function shards(): int
    {
        return 128;
    }

    public static function useDeadLetter(): bool
    {
        return false;
    }
}
