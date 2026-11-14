<?php

declare(strict_types=1);

namespace App\Amqp\Queue;

use App\Amqp\Consumer\AccountAdvReportConsumer;
use App\Amqp\Producer\AccountAdvReportProducer;
use Goletter\Amqp\Sharded\TenantBusinessShardedQueue;

/** accountAdvReport：同一 tenant_id + busine_id 串行，不同组合可并行。 */
final class AccountAdvReportQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'accountAdvReport';

    public static function consumerClass(): string
    {
        return AccountAdvReportConsumer::class;
    }

    public static function producerClass(): string
    {
        return AccountAdvReportProducer::class;
    }

    public static function serialFields(): array
    {
        return ['tenant_id', 'busine_id'];
    }

    /** 失败不重试：首次失败即 DROP 进死信 */
    public static function maxAttempts(): int
    {
        return 1;
    }

    public static function shards(): int
    {
        return 128;
    }

    public static function useDeadLetter(): bool
    {
        return false;
    }
}
