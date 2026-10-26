<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Amqp\Consumer;

use App\Amqp\AccountDemandQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Hyperf\Amqp\Result;
use PhpAmqpLib\Message\AMQPMessage;

use function Goletter\Utils\logging;

class AccountDemandConsumer extends ShardedConsumer
{
    public function consumeMessage($data, AMQPMessage $message): Result
    {
        $payload = is_array($data) ? $data : [];
        $key = AccountDemandQueue::serialKey($payload);

        logging([
            'shard' => $this->getShard(),
            'key' => $key,
            ...$payload,
        ], '账户需求消息队列', 'test');

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountDemandQueue::class;
    }
}
