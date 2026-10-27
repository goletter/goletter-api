<?php

declare(strict_types=1);

namespace App\Amqp;

use App\Amqp\Sharded\ShardedQueue;

/**
 * 账户分片队列注册表：消费进程与运维命令共用。
 */
final class AccountQueueRegistry
{
    /**
     * @return array<string, class-string<ShardedQueue>>
     */
    public static function all(): array
    {
        return [
            'accountBind' => AccountBindQueue::class,
            'accountDeduction' => AccountDeductionQueue::class,
            'accountRecharge' => AccountRechargeQueue::class,
            'accountReset' => AccountResetQueue::class,
            'accountUnBind' => AccountUnBindQueue::class,
        ];
    }

    /**
     * @return list<class-string<ShardedQueue>>
     */
    public static function classes(): array
    {
        return array_values(self::all());
    }

    /**
     * @return class-string<ShardedQueue>|null
     */
    public static function get(string $name): ?string
    {
        return self::all()[$name] ?? null;
    }
}
