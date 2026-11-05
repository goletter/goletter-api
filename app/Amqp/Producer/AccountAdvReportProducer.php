<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\Queue\AccountAdvReportQueue;
use Goletter\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: AccountAdvReportQueue::EXCHANGE, routingKey: '')]
class AccountAdvReportProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return AccountAdvReportQueue::class;
    }
}
