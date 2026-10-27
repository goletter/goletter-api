<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\AccountUnBindQueue;
use App\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountUnBindQueue::EXCHANGE, routingKey: '')]
class AccountUnBindProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountUnBindQueue::class;
    }
}
