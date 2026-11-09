<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountQueue;
use Goletter\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

class AccountConsumer extends ShardedConsumer
{
    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        // TODO: 业务处理

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountQueue::class;
    }
}
