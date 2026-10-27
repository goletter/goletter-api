<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\AccountRechargeQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\logging;

class AccountRechargeConsumer extends ShardedConsumer
{
    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $payload = is_array($data) ? $data : [];
        logging([
            'event' => 'handle',
            'shard' => $this->getShard(),
            'key' => AccountRechargeQueue::serialKey($payload),
            'attempts' => (int) ($payload['attempts'] ?? 0),
            'at' => date('H:i:s'),
            ...$payload,
        ], '账户充值消息队列', 'queue');

        $status = (int) ($payload['status'] ?? 0);
        // 仅测试：999 主动进死信；998 抛错触发重试耗尽进死信
        if ($status === 999) {
            return Result::DROP;
        }
        if ($status === 998) {
            throw new \RuntimeException('模拟失败，用于验证重试与死信');
        }

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountRechargeQueue::class;
    }
}
