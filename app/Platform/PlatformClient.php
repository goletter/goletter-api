<?php

declare(strict_types=1);

namespace App\Platform;

use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Throwable;

use function Goletter\Utils\cache;

/**
 * 调用平台端内部接口（鉴权、租户 Webhook 配置等）。
 */
class PlatformClient
{
    public function __construct(
        protected ClientFactory $clientFactory,
        protected ConfigInterface $config,
    ) {
    }

    /**
     * @param array{
     *   token?: string,
     *   app_id?: string,
     *   app_secret?: string,
     *   path: string,
     *   method: string
     * } $payload
     * @return array{ok: bool, message?: string, tenant_id?: int|string, user_id?: int|string, permissions?: array}
     */
    public function verify(array $payload): array
    {
        $module = (string) $this->config->get('platform.module', 'goletter-api');
        $body = array_merge($payload, ['module' => $module]);

        $ttl = (int) $this->config->get('platform.cache_ttl', 60);
        $cacheKey = $this->authCacheKey($body);

        if ($ttl > 0) {
            $cached = cache()->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = $this->requestJson('POST', (string) $this->config->get('platform.verify_path', '/internal/auth/verify'), $body);

        if ($ttl > 0 && ($result['ok'] ?? false)) {
            cache()->set($cacheKey, $result, $ttl);
        }

        return $result;
    }

    /**
     * 按租户拉取出站 Webhook 配置（配置表在平台端）。
     *
     * 期望平台响应（可包在 data 内）：
     * - found=false：该租户未配置
     * - enable / url / secret / retry_delay_ms / events
     *
     * @return array{ok: bool, found?: bool, enable?: bool, url?: string, secret?: string, retry_delay_ms?: int, events?: array, message?: string}
     */
    public function getWebhookConfig(int|string $tenantId): array
    {
        $module = (string) $this->config->get('platform.module', 'goletter-api');
        $path = (string) $this->config->get('platform.webhook_config_path', '/internal/webhook/config');

        return $this->requestJson('GET', $path, [], [
            'tenant_id' => (string) $tenantId,
            'module' => $module,
        ]);
    }

    /**
     * @param array<string, mixed> $jsonBody
     * @param array<string, scalar> $query
     * @return array<string, mixed>
     */
    protected function requestJson(string $method, string $path, array $jsonBody = [], array $query = []): array
    {
        $baseUri = (string) $this->config->get('platform.base_uri', '');
        if ($baseUri === '') {
            return ['ok' => false, 'message' => 'PLATFORM_BASE_URI is not configured'];
        }

        $appId = (string) $this->config->get('platform.app_id', '');
        $appSecret = (string) $this->config->get('platform.app_secret', '');

        $client = $this->clientFactory->create([
            'base_uri' => $baseUri . '/',
            'timeout' => (float) $this->config->get('platform.timeout', 3.0),
            'connect_timeout' => (float) $this->config->get('platform.connect_timeout', 1.0),
            'http_errors' => false,
        ]);

        $options = [
            'headers' => array_filter([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Module-App-Id' => $appId !== '' ? $appId : null,
                'X-Module-App-Secret' => $appSecret !== '' ? $appSecret : null,
            ]),
        ];
        if ($query !== []) {
            $options['query'] = $query;
        }
        if ($jsonBody !== [] && strtoupper($method) !== 'GET') {
            $options['json'] = $jsonBody;
        }

        try {
            $response = $client->request(strtoupper($method), ltrim($path, '/'), $options);
        } catch (GuzzleException|Throwable $e) {
            return ['ok' => false, 'message' => 'Platform unreachable: ' . $e->getMessage()];
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();
        $data = json_decode($raw, true);
        if (! is_array($data)) {
            return ['ok' => false, 'message' => 'Invalid platform response'];
        }

        // 兼容平台包一层 data 的响应结构
        if (isset($data['data']) && is_array($data['data'])) {
            $data = array_merge($data, $data['data']);
        }

        if ($status === 404) {
            return array_merge(['ok' => true, 'found' => false], $data);
        }

        if ($status >= 400) {
            return [
                'ok' => false,
                'message' => (string) ($data['message'] ?? $data['msg'] ?? 'Platform request failed'),
            ];
        }

        if (array_key_exists('ok', $data)) {
            return $data;
        }

        // 无 ok 字段时：2xx 视为成功
        return array_merge(['ok' => true], $data);
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function authCacheKey(array $body): string
    {
        $token = (string) ($body['token'] ?? '');
        $appId = (string) ($body['app_id'] ?? '');
        $module = (string) ($body['module'] ?? '');

        return 'platform:auth:' . sha1($module . '|' . $token . '|' . $appId);
    }
}
