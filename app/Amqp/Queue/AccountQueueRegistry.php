<?php

declare(strict_types=1);

namespace App\Amqp\Queue;

use Goletter\Amqp\Contract\QueueRegistryInterface;
use Goletter\Amqp\Sharded\ShardedQueue;

/**
 * 账户分片队列注册表：消费进程与运维命令共用。
 */
final class AccountQueueRegistry implements QueueRegistryInterface
{
    /**
     * @return array<string, class-string<ShardedQueue>>
     */
    public function all(): array
    {
        return [
            'accountInsight' => AccountInsightQueue::class,
            'accountBind' => AccountBindQueue::class,
            'accountDeduction' => AccountDeductionQueue::class,
            'accountRecharge' => AccountRechargeQueue::class,
            'accountReset' => AccountResetQueue::class,
            'accountUnBind' => AccountUnBindQueue::class,
            'notifyWebhook' => NotifyWebhookQueue::class,
        ];
    }

    /**
     * @return list<class-string<ShardedQueue>>
     */
    public function classes(): array
    {
        return array_values($this->all());
    }

    /**
     * @return null|class-string<ShardedQueue>
     */
    public function get(string $name): ?string
    {
        return $this->all()[$name] ?? null;
    }
}
