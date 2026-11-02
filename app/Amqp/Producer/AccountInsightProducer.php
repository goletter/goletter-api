<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\Queue\AccountInsightQueue;
use Goletter\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountInsightQueue::EXCHANGE, routingKey: '')]
class AccountInsightProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountInsightQueue::class;
    }
}
