<?php

declare(strict_types=1);

namespace App\Amqp\Queue;

use App\Amqp\Consumer\NotifyWebhookConsumer;
use App\Amqp\Producer\NotifyWebhookProducer;
use App\Amqp\Sharded\TenantBusinessShardedQueue;
use Hyperf\Contract\ConfigInterface;

use function Hyperf\Support\make;

/** 出站 Webhook：同一 tenant_id + busine_id 串行推送，不同组合可并行。 */
final class NotifyWebhookQueue extends TenantBusinessShardedQueue
{
    public const EXCHANGE = 'notifyWebhook';

    public static function consumerClass(): string
    {
        return NotifyWebhookConsumer::class;
    }

    public static function producerClass(): string
    {
        return NotifyWebhookProducer::class;
    }

    public static function maxAttempts(): int
    {
        return max(1, (int) make(ConfigInterface::class)->get('webhook.max_attempts', 5));
    }

    /** 全局默认；消息级 retry_delay_ms（平台租户配置）优先。 */
    public static function retryDelayMs(): int
    {
        return max(0, (int) make(ConfigInterface::class)->get('webhook.retry_delay_ms', 3000));
    }
}
