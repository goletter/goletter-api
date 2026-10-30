<?php

declare(strict_types=1);

namespace App\Service\Webhook;

use App\Amqp\Queue\NotifyWebhookQueue;
use App\Model\AccountDemand;
use App\Service\TenantWebhookConfigService;
use Hyperf\Contract\ConfigInterface;
use Throwable;

use function Goletter\Utils\logging;

/**
 * 业务结果出站通知：按租户从平台拉取动态配置，再投递 NotifyWebhook 队列。
 *
 * URL 优先级：
 * 1. 单次请求/单据 extend.callback_url|webhook_url
 * 2. 平台租户 events[event]
 * 3. 平台租户 url
 * 4. 全局 events[event]
 * 5. 全局 default_url
 */
class WebhookNotifier
{
    public function __construct(
        protected ConfigInterface $config,
        protected TenantWebhookConfigService $tenantWebhookConfigs,
    ) {
    }

    /**
     * 推送账户需求单结果（成功 / 失败）。
     */
    public function notifyAccountDemand(AccountDemand $demand, string $event): bool
    {
        $extend = $this->normalizeExtend($demand->extend ?? null);
        $url = $this->firstNonEmptyString([
            $extend['callback_url'] ?? null,
            $extend['webhook_url'] ?? null,
        ]);

        return $this->dispatch($event, [
            'account_demand_id' => $demand->id,
            'tenant_id' => $demand->tenant_id,
            'busine_id' => $demand->busine_code,
            'platform' => $demand->platform,
            'order_no' => $demand->order_no,
            'type' => $demand->type,
            'code' => $demand->code,
            'name' => $demand->name,
            'money' => $demand->money,
            'currency' => $demand->currency,
            'status' => $demand->status,
            'transaction_id' => $demand->transaction_id,
            'remark' => $demand->remark,
        ], $url);
    }

    /**
     * @param array<string, mixed> $payload 业务数据（需含 tenant_id；建议含 busine_id）
     */
    public function dispatch(string $event, array $payload, ?string $url = null, ?string $secret = null): bool
    {
        $tenantId = (string) ($payload['tenant_id'] ?? '');
        $tenant = $tenantId !== '' ? $this->tenantWebhookConfigs->findResolved($tenantId) : null;

        if (! $this->isEnabled($tenant)) {
            return false;
        }

        $resolvedUrl = $this->resolveUrl($event, $url, $tenant);
        if ($resolvedUrl === null || $resolvedUrl === '') {
            return false;
        }

        $resolvedSecret = $secret;
        if ($resolvedSecret === null || $resolvedSecret === '') {
            $resolvedSecret = $this->resolveSecret($tenant);
        }

        $busineId = $payload['busine_id'] ?? $payload['busine_code'] ?? '0';
        if ($busineId === null || $busineId === '') {
            $busineId = '0';
        }

        try {
            return NotifyWebhookQueue::produce([
                'tenant_id' => $tenantId !== '' ? $tenantId : 0,
                'busine_id' => $busineId,
                'url' => $resolvedUrl,
                'secret' => $resolvedSecret,
                'retry_delay_ms' => $this->resolveRetryDelayMs($tenant),
                'event' => $event,
                'payload' => $payload,
            ]);
        } catch (Throwable $e) {
            logging([
                'event' => 'webhook_produce_failed',
                'webhook_event' => $event,
                'tenant_id' => $tenantId,
                'url' => $resolvedUrl,
                'error' => $e->getMessage(),
            ], 'Webhook 投递失败', 'webhook');

            return false;
        }
    }

    /**
     * @param null|array{enable: bool, url: string, secret: string, retry_delay_ms?: int, events: array<string, string>} $tenant
     */
    protected function isEnabled(?array $tenant): bool
    {
        if ($tenant !== null) {
            return (bool) ($tenant['enable'] ?? false);
        }

        return (bool) $this->config->get('webhook.enable', false);
    }

    /**
     * @param null|array{enable: bool, url: string, secret: string, retry_delay_ms?: int, events: array<string, string>} $tenant
     */
    protected function resolveRetryDelayMs(?array $tenant): int
    {
        if ($tenant !== null && array_key_exists('retry_delay_ms', $tenant)) {
            return max(0, (int) $tenant['retry_delay_ms']);
        }

        return max(0, (int) $this->config->get('webhook.retry_delay_ms', 3000));
    }

    /**
     * @param null|array{enable: bool, url: string, secret: string, retry_delay_ms?: int, events: array<string, string>} $tenant
     */
    protected function resolveUrl(string $event, ?string $explicitUrl, ?array $tenant): ?string
    {
        if ($explicitUrl !== null && $explicitUrl !== '') {
            return $explicitUrl;
        }

        if ($tenant !== null) {
            $tenantEventUrl = (string) ($tenant['events'][$event] ?? '');
            if ($tenantEventUrl !== '') {
                return $tenantEventUrl;
            }
            $tenantUrl = (string) ($tenant['url'] ?? '');
            if ($tenantUrl !== '') {
                return $tenantUrl;
            }
        }

        $eventUrl = (string) $this->config->get('webhook.events.' . $event, '');
        if ($eventUrl !== '') {
            return $eventUrl;
        }

        $default = (string) $this->config->get('webhook.default_url', '');

        return $default !== '' ? $default : null;
    }

    /**
     * @param null|array{enable: bool, url: string, secret: string, retry_delay_ms?: int, events: array<string, string>} $tenant
     */
    protected function resolveSecret(?array $tenant): string
    {
        if ($tenant !== null) {
            $secret = (string) ($tenant['secret'] ?? '');
            if ($secret !== '') {
                return $secret;
            }
        }

        return (string) $this->config->get('webhook.secret', '');
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeExtend(mixed $extend): array
    {
        if (is_array($extend)) {
            return $extend;
        }
        if (is_string($extend) && $extend !== '') {
            $decoded = json_decode($extend, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * @param list<null|string> $candidates
     */
    protected function firstNonEmptyString(array $candidates): ?string
    {
        foreach ($candidates as $value) {
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
