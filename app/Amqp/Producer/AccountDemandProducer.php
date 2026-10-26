<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\AccountDemandQueue;
use App\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountDemandQueue::EXCHANGE, routingKey: '')]
class AccountDemandProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountDemandQueue::class;
    }
}
