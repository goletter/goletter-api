<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\Queue\AccountDeductionQueue;
use App\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountDeductionQueue::EXCHANGE, routingKey: '')]
class AccountDeductionProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountDeductionQueue::class;
    }
}
