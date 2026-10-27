<?php

declare(strict_types=1);

namespace App\Process;

use App\Amqp\AccountBindQueue;
use App\Amqp\AccountDeductionQueue;
use App\Amqp\AccountRechargeQueue;
use App\Amqp\AccountResetQueue;
use App\Amqp\AccountUnBindQueue;
use App\Amqp\Sharded\ShardedConsumeProcess;
use Hyperf\Process\Annotation\Process;

/**
 * 账户相关分片队列统一消费进程。
 * 新增同类队列时，往 queues() 加一行即可。
 */
#[Process(name: 'account-sharded-consume', nums: 1)]
class AccountShardedConsumeProcess extends ShardedConsumeProcess
{
    protected function queues(): array
    {
        return [
            AccountBindQueue::class,
            AccountDeductionQueue::class,
            AccountRechargeQueue::class,
            AccountResetQueue::class,
            AccountUnBindQueue::class,
        ];
    }
}
