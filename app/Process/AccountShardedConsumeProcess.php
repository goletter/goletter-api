<?php

declare(strict_types=1);

namespace App\Process;

use App\Amqp\Queue\AccountQueueRegistry;
use App\Amqp\Sharded\ShardedConsumeProcess;

/**
 * 账户相关分片队列统一消费进程。
 *
 * 在 config/autoload/processes.php 注册（勿再加 #[Process]，避免与配置双重拉起）。
 * 新增同类队列时，改 AccountQueueRegistry 即可。
 */
class AccountShardedConsumeProcess extends ShardedConsumeProcess
{
    public string $name = 'account-sharded-consume';

    public int $nums = 1;

    protected function queues(): array
    {
        return AccountQueueRegistry::classes();
    }
}
