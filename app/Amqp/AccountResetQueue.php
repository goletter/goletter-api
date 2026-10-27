<?php

declare(strict_types=1);

namespace App\Amqp;

use App\Amqp\Consumer\AccountResetConsumer;
use App\Amqp\Producer\AccountResetProducer;
use App\Amqp\Sharded\TenantBusinessShardedQueue;

/** 账户重置：同一 tenant_id + busine_id 串行，不同组合可并行。 */
final class AccountResetQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'accountReset';

    public static function consumerClass(): string
    {
        return AccountResetConsumer::class;
    }

    public static function producerClass(): string
    {
        return AccountResetProducer::class;
    }
}
