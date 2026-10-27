<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\AccountDeductionQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\logging;

class AccountDeductionConsumer extends ShardedConsumer
{
    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $payload = is_array($data) ? $data : [];
        logging([
            'event' => 'handle',
            'shard' => $this->getShard(),
            'key' => AccountDeductionQueue::serialKey($payload),
            'attempts' => (int) ($payload['attempts'] ?? 0),
            'at' => date('H:i:s'),
            ...$payload,
        ], '账户扣款消息队列', 'test');

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountDeductionQueue::class;
    }
}
