<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountDeductionQueue;
use Goletter\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

class AccountDeductionConsumer extends ShardedConsumer
{
    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountDeductionQueue::class;
    }
}
