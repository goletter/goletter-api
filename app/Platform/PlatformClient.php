<?php

declare(strict_types=1);

namespace App\Platform;

use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Throwable;

use function Goletter\Utils\cache;

/**
 * 调用平台端内部鉴权接口。
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
        $cacheKey = $this->cacheKey($body);

        if ($ttl > 0) {
            $cached = cache()->get($cacheKey);
            if (is_array($cached)) {
                return $cached;
            }
        }

        $result = $this->requestVerify($body);

        if ($ttl > 0 && ($result['ok'] ?? false)) {
            cache()->set($cacheKey, $result, $ttl);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $body
     * @return array{ok: bool, message?: string, tenant_id?: int|string, user_id?: int|string, permissions?: array}
     */
    protected function requestVerify(array $body): array
    {
        $baseUri = (string) $this->config->get('platform.base_uri', '');
        if ($baseUri === '') {
            return ['ok' => false, 'message' => 'PLATFORM_BASE_URI is not configured'];
        }

        $path = (string) $this->config->get('platform.verify_path', '/internal/auth/verify');
        $appId = (string) $this->config->get('platform.app_id', '');
        $appSecret = (string) $this->config->get('platform.app_secret', '');

        $client = $this->clientFactory->create([
            'base_uri' => $baseUri . '/',
            'timeout' => (float) $this->config->get('platform.timeout', 3.0),
            'connect_timeout' => (float) $this->config->get('platform.connect_timeout', 1.0),
            'http_errors' => false,
        ]);

        try {
            $response = $client->post(ltrim($path, '/'), [
                'headers' => array_filter([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Module-App-Id' => $appId !== '' ? $appId : null,
                    'X-Module-App-Secret' => $appSecret !== '' ? $appSecret : null,
                ]),
                'json' => $body,
            ]);
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

        if ($status >= 400) {
            return [
                'ok' => false,
                'message' => (string) ($data['message'] ?? $data['msg'] ?? 'Unauthorized'),
            ];
        }

        if (array_key_exists('ok', $data)) {
            return $data;
        }

        // 无 ok 字段时：2xx 且带 tenant_id 视为通过
        if (isset($data['tenant_id'])) {
            return array_merge(['ok' => true], $data);
        }

        return ['ok' => false, 'message' => (string) ($data['message'] ?? 'Verify failed')];
    }

    /**
     * @param array<string, mixed> $body
     */
    protected function cacheKey(array $body): string
    {
        $token = (string) ($body['token'] ?? '');
        $appId = (string) ($body['app_id'] ?? '');
        $module = (string) ($body['module'] ?? '');

        return 'platform:auth:' . sha1($module . '|' . $token . '|' . $appId);
    }
}
