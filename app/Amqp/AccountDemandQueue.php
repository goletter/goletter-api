<?php

declare(strict_types=1);

namespace App\Amqp;

use App\Amqp\Consumer\AccountDemandConsumer;
use App\Amqp\Sharded\ShardedQueue;
use InvalidArgumentException;

/**
 * 账户需求：同一 tenant_id + busine_id 串行，不同组合可并行。
 */
final class AccountDemandQueue extends ShardedQueue
{
    public const EXCHANGE = 'accountDemand';

    public static function exchange(): string
    {
        return self::EXCHANGE;
    }

    public static function serialKey(array $data): string
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $businessId = (int) ($data['busine_id'] ?? $data['business_id'] ?? 0);
        if ($tenantId <= 0 || $businessId <= 0) {
            throw new InvalidArgumentException('accountDemand 消息必须带 tenant_id 和 busine_id');
        }

        return $tenantId . ':' . $businessId;
    }

    public static function consumerClass(): string
    {
        return AccountDemandConsumer::class;
    }

    /** 同组串行：本条处理完后隔 2 秒再拉下一条 */
    public static function intervalMs(): int
    {
        return 2000;
    }
}
