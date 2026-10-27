<?php

declare(strict_types=1);

namespace App\Amqp\Sharded;

use InvalidArgumentException;

/**
 * 账户类分片队列公共约定：同一 tenant_id + busine_id 串行。
 *
 * 子类只需声明 EXCHANGE，并实现 consumerClass() / producerClass()。
 */
abstract class TenantBusinessShardedQueue extends ShardedQueue
{
    public const EXCHANGE = '';

    public static function exchange(): string
    {
        $exchange = static::EXCHANGE;
        if ($exchange === '') {
            throw new InvalidArgumentException(static::class . ' 必须定义 EXCHANGE');
        }

        return $exchange;
    }

    public static function serialKey(array $data): string
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $businessId = (int) ($data['busine_id'] ?? $data['business_id'] ?? 0);
        if ($tenantId <= 0 || $businessId <= 0) {
            throw new InvalidArgumentException(static::exchange() . ' 消息必须带 tenant_id 和 busine_id');
        }

        return $tenantId . ':' . $businessId;
    }

    /** 同组串行：本条处理完后隔 2 秒再拉下一条 */
    public static function intervalMs(): int
    {
        return 2000;
    }
}
