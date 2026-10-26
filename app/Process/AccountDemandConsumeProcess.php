<?php

declare(strict_types=1);

namespace App\Process;

use App\Amqp\AccountDemandQueue;
use App\Amqp\Consumer\AccountDemandConsumer;
use App\Amqp\Sharded\ShardedConsumeProcess;
use Hyperf\Process\Annotation\Process;

/**
 * 账户需求分片消费进程：为每个 shard 起一个协程监听 accountDemand.{n}
 */
#[Process(name: 'account-demand-consume', nums: 1)]
class AccountDemandConsumeProcess extends ShardedConsumeProcess
{
    protected function queueClass(): string
    {
        return AccountDemandQueue::class;
    }

    protected function consumerClass(): string
    {
        return AccountDemandConsumer::class;
    }
}
