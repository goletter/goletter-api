<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\Queue\AccountBindQueue;
use App\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountBindQueue::EXCHANGE, routingKey: '')]
class AccountBindProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountBindQueue::class;
    }
}
