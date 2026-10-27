<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\Queue\AccountRechargeQueue;
use App\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountRechargeQueue::EXCHANGE, routingKey: '')]
class AccountRechargeProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountRechargeQueue::class;
    }
}
