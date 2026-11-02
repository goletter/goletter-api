<?php

declare(strict_types=1);

namespace App\Amqp\Queue;

use App\Amqp\Consumer\AccountInsightConsumer;
use App\Amqp\Producer\AccountInsightProducer;
use App\Amqp\Sharded\TenantBusinessShardedQueue;

/** accountInsight：同一 tenant_id + busine_id 串行，不同组合可并行。 */
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

    public static function shards(): int
    {
        return 48;
    }
}
