<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\AccountResetQueue;
use App\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountResetQueue::EXCHANGE, routingKey: '')]
class AccountResetProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountResetQueue::class;
    }
}
