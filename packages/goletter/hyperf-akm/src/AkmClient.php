<?php

declare(strict_types=1);

namespace Goletter\Akm;

use Goletter\Akm\Exceptions\AkmTokenExpiredException;
use Goletter\Akm\Exceptions\AkmsApiException;
use Goletter\Adv\Support\RotatesAccessTokens;
use Goletter\Adv\Support\SafeLogs;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class AkmClient
{
    use RotatesAccessTokens;
    use SafeLogs;

    public const DEFAULT_BASE_URI = 'https://akms-openapi.bluemediagroup.cn';

    protected Client $http;

    protected string $baseUri;

    protected array $defaultHeaders = [];

    /**
     * 是否记录每次请求耗时（配置 akm.log_request / AKM_LOG_REQUEST）。
     */
    protected bool $logRequest = false;

    /**
     * @var null|callable(array $log): void
     */
    protected static $callLogHandler = null;

    /**
     * @param string|list<string> $accessToken 单个或多个 token；失败时按顺序轮询下一个
     */
    public function __construct(
        string|array $accessToken,
        ?string $baseUri = null,
        ?float $timeout = null,
        ?bool $logRequest = null,
    ) {
        $config = self::packageConfig();
        $baseUri ??= (string) ($config['base_uri'] ?? '');
        if ($baseUri === '') {
            $baseUri = self::env('AKM_BASE_URL', self::env('CKM_BASE_URL', self::DEFAULT_BASE_URI));
        }
        $timeout ??= (float) ($config['timeout'] ?? 10);
        $logRequest ??= (bool) ($config['log_request'] ?? false);

        $this->bootstrapAccessTokens($accessToken);
        $this->baseUri = rtrim($baseUri, '/');
        $this->logRequest = $logRequest;
        $this->http = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => $timeout,
        ]);

        $headers = $config['default_headers'] ?? [];
        if (is_array($headers) && $headers !== []) {
            $this->defaultHeaders = $headers;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function packageConfig(): array
    {
        if (! class_exists(\Hyperf\Context\ApplicationContext::class)) {
            return [];
        }

        $context = \Hyperf\Context\ApplicationContext::class;
        if (! $context::hasContainer()) {
            return [];
        }

        $container = $context::getContainer();
        if (! $container->has(\Hyperf\Contract\ConfigInterface::class)) {
            return [];
        }

        $config = $container->get(\Hyperf\Contract\ConfigInterface::class)->get('akm', []);

        return is_array($config) ? $config : [];
    }

    private static function env(string $key, string $default = ''): string
    {
        if (function_exists('Hyperf\\Support\\env')) {
            return (string) \Hyperf\Support\env($key, $default);
        }

        $value = getenv($key);
        if ($value === false) {
            return (string) ($_ENV[$key] ?? $default);
        }

        return (string) $value;
    }

    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;

        return $this;
    }

    /**
     * @param null|callable(array $log): void $handler
     */
    public static function setCallLogHandler(?callable $handler): void
    {
        self::$callLogHandler = $handler;
    }

    public function get(string $path, array $query = [], string $api_interface = ''): array
    {
        return $this->request('GET', $path, [
            'query' => $query,
        ], $api_interface);
    }

    public function postJson(string $path, array $body = [], array $query = [], string $api_interface = ''): array
    {
        $options = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ];

        if ($body !== []) {
            $options['json'] = $body;
        }

        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->request('POST', $path, $options, $api_interface);
    }

    /**
     * multipart/form-data 请求（主要用于文件上传）
     */
    public function postMultipart(string $path, array $multipart, array $query = [], string $api_interface = ''): array
    {
        $options = [
            'multipart' => $multipart,
        ];

        if ($query !== []) {
            $options['query'] = $query;
        }

        return $this->request('POST', $path, $options, $api_interface);
    }

    /**
     * 底层请求封装，自动携带 Authorization 头，并按文档处理 code/info/data
     */
    protected function request(string $method, string $path, array $options = [], string $api_interface = ''): array
    {
        return $this->withTokenFailover(function () use ($method, $path, $options, $api_interface): array {
            $headers = array_merge($this->defaultHeaders, $options['headers'] ?? []);
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
            $options['headers'] = $headers;

            if (! str_starts_with($path, '/')) {
                $path = '/' . $path;
            }

            $startedAt = microtime(true);

            try {
                $response = $this->http->request($method, $path, $options);
                $durationMs = $this->elapsedMs($startedAt);
                $body = (string) $response->getBody();
                $decoded = json_decode($body, true);

                if (! is_array($decoded)) {
                    throw new AkmsApiException('Invalid JSON response from AKMS OpenAPI', 0, [
                        'raw' => $body,
                    ]);
                }

                $code = $decoded['code'] ?? null;
                if ($code === null || (int) $code !== 0) {
                    throw new AkmsApiException(
                        (string) ($decoded['info'] ?? 'AKMS OpenAPI error'),
                        (int) ($code ?? 0),
                        $decoded
                    );
                }

                $this->pushCallLog($method, $path, $api_interface, true, '', $this->accessToken, $durationMs);

                return $decoded;
            } catch (AkmsApiException $e) {
                $this->pushCallLog(
                    $method,
                    $path,
                    $api_interface,
                    false,
                    $e->getMessage(),
                    $this->accessToken,
                    $this->elapsedMs($startedAt)
                );
                throw $e;
            } catch (RequestException $e) {
                $durationMs = $this->elapsedMs($startedAt);
                $response = $e->getResponse();
                $decoded = [];
                if ($response !== null) {
                    $decoded = json_decode((string) $response->getBody(), true) ?: [];
                    if ($response->getStatusCode() === 401) {
                        $this->pushCallLog($method, $path, $api_interface, false, $e->getMessage(), $this->accessToken, $durationMs);
                        throw new AkmTokenExpiredException($e->getMessage(), 401, $decoded, $e);
                    }
                }

                $message = (string) ($decoded['info'] ?? $e->getMessage());
                $this->pushCallLog($method, $path, $api_interface, false, $message, $this->accessToken, $durationMs);

                throw new AkmsApiException($message, (int) $e->getCode(), $decoded, $e);
            } catch (GuzzleException $e) {
                $this->pushCallLog(
                    $method,
                    $path,
                    $api_interface,
                    false,
                    $e->getMessage(),
                    $this->accessToken,
                    $this->elapsedMs($startedAt)
                );
                throw new AkmsApiException($e->getMessage(), (int) $e->getCode(), [], $e);
            }
        });
    }

    protected function elapsedMs(float $startedAt): int
    {
        return (int) max(0, round((microtime(true) - $startedAt) * 1000));
    }

    protected function pushCallLog(
        string $method,
        string $path,
        string $apiInterface,
        bool $success,
        string $error = '',
        string $token = '',
        int $durationMs = 0,
    ): void {
        if ($apiInterface === '') {
            $normalized = preg_replace('#^/open_api/v\d+(?:\.\d+)?#', '', $path) ?: $path;
            $normalized = preg_replace('#/\d+(?=/|$)#', '/{id}', $normalized) ?: $normalized;
            $apiInterface = strtoupper($method) . ':' . $normalized;
        }

        $payload = [
            'message' => $error,
            'url' => $path,
            'api_interface' => $apiInterface,
            'success' => $success,
            'token' => $token,
            'duration_ms' => $durationMs,
        ];

        // 失败始终尝试写 call_logs；开启 log_request 时成功请求也写
        if ($this->logRequest || $error !== '' || ! $success) {
            $this->safeLog($payload, '接口日志', 'call_logs');
        }

        if (! is_callable(self::$callLogHandler)) {
            return;
        }

        (self::$callLogHandler)([
            'token' => $token,
            'api_interface' => $apiInterface,
            'is_success' => $success ? 1 : 0,
            'error' => $error,
            'duration_ms' => $durationMs,
            'call_hour' => date('Y-m-d H:00:00'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

}
