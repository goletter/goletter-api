<?php

declare(strict_types=1);

namespace App\Amqp;

use App\Amqp\Consumer\AccountDeductionConsumer;
use App\Amqp\Producer\AccountDeductionProducer;
use App\Amqp\Sharded\TenantBusinessShardedQueue;

/** 账户扣款：同一 tenant_id + busine_id 串行，不同组合可并行。 */
final class AccountDeductionQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'accountDeduction';

    public static function consumerClass(): string
    {
        return AccountDeductionConsumer::class;
    }

    public static function producerClass(): string
    {
        return AccountDeductionProducer::class;
    }
}
