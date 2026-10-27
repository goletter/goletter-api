<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\Queue\AccountUnBindQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\logging;

class AccountUnBindConsumer extends ShardedConsumer
{
    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $payload = is_array($data) ? $data : [];
        logging([
            'event' => 'handle',
            'shard' => $this->getShard(),
            'key' => AccountUnBindQueue::serialKey($payload),
            'attempts' => (int) ($payload['attempts'] ?? 0),
            'at' => date('H:i:s'),
            ...$payload,
        ], '账户解绑消息队列', 'test');

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountUnBindQueue::class;
    }
}
