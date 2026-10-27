<?php

declare(strict_types=1);

namespace App\Process;

use App\Amqp\AccountQueueRegistry;
use App\Amqp\Sharded\ShardedConsumeProcess;
use Hyperf\Process\Annotation\Process;

/**
 * 账户相关分片队列统一消费进程。
 * 新增同类队列时，改 AccountQueueRegistry 即可。
 */
#[Process(name: 'account-sharded-consume', nums: 1)]
class AccountShardedConsumeProcess extends ShardedConsumeProcess
{
    protected function queues(): array
    {
        return AccountQueueRegistry::classes();
    }
}
