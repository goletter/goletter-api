<?php

declare(strict_types=1);

namespace App\Amqp\Queue;

use App\Amqp\Consumer\AccountBindConsumer;
use App\Amqp\Producer\AccountBindProducer;
use App\Amqp\Sharded\TenantBusinessShardedQueue;

/** 账户绑定：同一 tenant_id + busine_id 串行，不同组合可并行。 */
final class AccountBindQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'accountBind';

    public static function consumerClass(): string
    {
        return AccountBindConsumer::class;
    }

    public static function producerClass(): string
    {
        return AccountBindProducer::class;
    }
}
