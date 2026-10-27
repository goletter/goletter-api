<?php

declare(strict_types=1);

namespace App\Amqp;

use App\Amqp\Consumer\AccountUnBindConsumer;
use App\Amqp\Producer\AccountUnBindProducer;
use App\Amqp\Sharded\TenantBusinessShardedQueue;

/** 账户解绑：同一 tenant_id + busine_id 串行，不同组合可并行。 */
final class AccountUnBindQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'accountUnBind';

    public static function consumerClass(): string
    {
        return AccountUnBindConsumer::class;
    }

    public static function producerClass(): string
    {
        return AccountUnBindProducer::class;
    }
}
