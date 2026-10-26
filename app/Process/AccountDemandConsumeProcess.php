<?php

declare(strict_types=1);

namespace App\Process;

use App\Amqp\AccountDemandQueue;
use App\Amqp\Consumer\AccountDemandConsumer;
use App\Amqp\Sharded\ShardedConsumeProcess;
use Hyperf\Process\Annotation\Process;

#[Process(name: 'account-demand', nums: 1)]
class AccountDemandConsumeProcess extends ShardedConsumeProcess
{
    public string $name = 'account-demand';

    protected function queueClass(): string
    {
        return AccountDemandQueue::class;
    }

    protected function consumerClass(): string
    {
        return AccountDemandConsumer::class;
    }
}
