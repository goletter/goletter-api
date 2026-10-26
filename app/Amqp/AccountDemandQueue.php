<?php

declare(strict_types=1);

namespace App\Amqp;

use InvalidArgumentException;

/**
 * 按 tenant_id + busine_id 分片。
 * 同一个组合永远进同一条队列（串行），不同组合落到不同分片（可并行）。
 */
final class AccountDemandQueue
{
    public const EXCHANGE = 'accountDemand';

    public const SHARDS = 16;

    public static function serialKey(array $data): string
    {
        $tenantId = (int) ($data['tenant_id'] ?? 0);
        $businessId = (int) ($data['busine_id'] ?? $data['business_id'] ?? 0);
        if ($tenantId <= 0 || $businessId <= 0) {
            throw new InvalidArgumentException('accountDemand 消息必须带 tenant_id 和 busine_id');
        }

        return $tenantId . ':' . $businessId;
    }

    public static function shard(array $data): int
    {
        $hash = (int) sprintf('%u', crc32(self::serialKey($data)));

        return $hash % self::SHARDS;
    }

    public static function queue(int $shard): string
    {
        return self::EXCHANGE . '.' . $shard;
    }

    public static function routingKey(int $shard): string
    {
        return 'shard.' . $shard;
    }
}
