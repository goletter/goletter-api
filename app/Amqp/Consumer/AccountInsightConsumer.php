<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountInsightQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

class AccountInsightConsumer extends ShardedConsumer
{
    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        // TODO: 业务处理

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountInsightQueue::class;
    }
}
