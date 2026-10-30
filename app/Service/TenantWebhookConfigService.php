<?php

declare(strict_types=1);

namespace App\Service;

use App\Platform\PlatformClient;
use Hyperf\Contract\ConfigInterface;

use function Goletter\Utils\cache;
use function Goletter\Utils\logging;

/**
 * 从平台端拉取租户 Webhook 动态配置（表在平台，本子模块不落库）。
 */
class TenantWebhookConfigService
{
    public function __construct(
        protected PlatformClient $platform,
        protected ConfigInterface $config,
    ) {
    }

    /**
     * @return null|array{
     *   enable: bool,
     *   url: string,
     *   secret: string,
     *   retry_delay_ms: int,
     *   events: array<string, string>
     * }
     */
    public function findResolved(int|string $tenantId): ?array
    {
        $tenantId = (string) $tenantId;
        if ($tenantId === '') {
            return null;
        }

        $ttl = (int) $this->config->get('webhook.tenant_cache_ttl', 60);
        $cacheKey = $this->cacheKey($tenantId);

        if ($ttl > 0) {
            $cached = cache()->get($cacheKey);
            if ($cached === false) {
                return null;
            }
            if (is_array($cached)) {
                return $cached;
            }
        }

        $raw = $this->platform->getWebhookConfig($tenantId);
        if (! ($raw['ok'] ?? false)) {
            logging([
                'event' => 'webhook_config_fetch_failed',
                'tenant_id' => $tenantId,
                'message' => $raw['message'] ?? 'unknown',
            ], '从平台拉取 Webhook 配置失败', 'webhook');

            if ($ttl > 0) {
                cache()->set($cacheKey, false, min($ttl, 15));
            }

            return null;
        }

        // 平台明确无配置
        if (($raw['found'] ?? true) === false) {
            if ($ttl > 0) {
                cache()->set($cacheKey, false, $ttl);
            }

            return null;
        }

        $events = $raw['events'] ?? [];
        if (! is_array($events)) {
            $events = [];
        }

        $resolved = [
            'enable' => (bool) ($raw['enable'] ?? false),
            'url' => (string) ($raw['url'] ?? ''),
            'secret' => (string) ($raw['secret'] ?? ''),
            'retry_delay_ms' => max(0, (int) ($raw['retry_delay_ms'] ?? $this->config->get('webhook.retry_delay_ms', 3000))),
            'events' => $events,
        ];

        if ($ttl > 0) {
            cache()->set($cacheKey, $resolved, $ttl);
        }

        return $resolved;
    }

    public function forgetCache(string $tenantId): void
    {
        if ($tenantId === '') {
            return;
        }
        cache()->delete($this->cacheKey($tenantId));
    }

    protected function cacheKey(string $tenantId): string
    {
        $module = (string) $this->config->get('platform.module', 'goletter-api');

        return 'webhook:tenant:' . $module . ':' . $tenantId;
    }
}
