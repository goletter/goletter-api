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

use App\Amqp\Queue\AccountRechargeQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Goletter\Docs\DocsManager;
use Hyperf\Amqp\Result;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;

class AccountRechargeConsumer extends ShardedConsumer
{
    #[Inject]
    protected DocsManager $docs;

    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return AccountRechargeQueue::class;
    }
}
