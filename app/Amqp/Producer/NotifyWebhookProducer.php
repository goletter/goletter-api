<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use App\Amqp\Queue\NotifyWebhookQueue;
use App\Amqp\Sharded\ShardedProducer;
use Hyperf\Amqp\Annotation\Producer;

#[Producer(exchange: NotifyWebhookQueue::EXCHANGE, routingKey: '')]
class NotifyWebhookProducer extends ShardedProducer
{
    protected static function queueClass(): string
    {
        return NotifyWebhookQueue::class;
    }
}
