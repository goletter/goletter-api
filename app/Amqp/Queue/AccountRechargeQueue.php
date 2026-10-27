<?php

declare(strict_types=1);

namespace App\Amqp\Queue;

use App\Amqp\Consumer\AccountRechargeConsumer;
use App\Amqp\Producer\AccountRechargeProducer;
use App\Amqp\Sharded\TenantBusinessShardedQueue;

/** 账户充值：同一 tenant_id + busine_id 串行，不同组合可并行。 */
final class AccountRechargeQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'accountRecharge';

    public static function consumerClass(): string
    {
        return AccountRechargeConsumer::class;
    }

    public static function producerClass(): string
    {
        return AccountRechargeProducer::class;
    }
}