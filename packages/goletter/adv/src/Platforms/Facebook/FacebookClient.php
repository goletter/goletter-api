<?php

namespace Goletter\Adv\Platforms\Facebook;

use GuzzleHttp\Client;
use Goletter\Adv\Platforms\Facebook\Exceptions\FacebookApiException;
use Goletter\Adv\Platforms\Facebook\Exceptions\FacebookTokenExpiredException;
use GuzzleHttp\Exception\RequestException;

class FacebookClient
{
    protected Client $http;
    protected string $accessToken;

    protected int $busineId;

    protected int $platformId;
    protected string $baseUri;

    protected array $defaultHeaders = [
        'Accept-Encoding' => 'identity',
    ];

    public function __construct(
        string $accessToken,
        int $busineId = 0,
        int $platformId = 0,
        string $apiVersion = 'v24.0'
    ) {
        $this->accessToken = $accessToken;
        $this->busineId = $busineId;
        $this->platformId = $platformId;
        $this->baseUri = "https://graph.facebook.com/{$apiVersion}";

        $this->http = new Client([
            'base_uri' => $this->baseUri,
            'timeout'  => 60,
            // 'verify' => false, // 如果需要忽略 SSL 验证可以取消注释
        ]);
    }

    /**
     * 设置默认请求头
     */
    public function setDefaultHeaders(array $headers): self
    {
        $this->defaultHeaders = $headers;
        return $this;
    }

    /* ========= 基础 HTTP ========= */

    public function get(string $uri, array $query = [], string $api_interface = ''): array
    {
        return $this->request('GET', $uri, $query, [], $api_interface);
    }

    public function post(string $uri, array $body = [], array $query = [], string $api_interface = ''): array
    {
        return $this->request('POST', $uri, $query, $body, $api_interface);
    }

    public function delete(string $uri, array $query = [], string $api_interface = ''): array
    {
        return $this->request('DELETE', $uri, $query, [], $api_interface);
    }

    protected function request(
        string $method,
        string $uri,
        array $query = [],
        array $body = [],
        string $api_interface = ''
    ): array {
        try {
            $options = [
                'query' => array_merge($query, [
                    'access_token' => $this->accessToken,
                ]),
            ];

            if (!empty($this->defaultHeaders)) {
                $options['headers'] = $this->defaultHeaders;
            }

            if (!empty($body)) {
                $options['json'] = $body;
            }

            // 获取 base_uri 的路径部分（包含版本号，如 /v24.0）
            $basePath = parse_url($this->baseUri, PHP_URL_PATH);
            // 提取版本号（如 v24.0）
            $apiVersion = $basePath ? trim($basePath, '/') : 'v24.0';
            $uri = $this->normalizeUri($uri);

            $response = $this->http->request($method, $uri, $options);
            $this->captureAppUsage($response, $this->accessToken, $this->busineId, $this->platformId);
            $data = json_decode((string) $response->getBody(), true);

            if (isset($data['error'])) {
                $this->pushCallLog($method, $uri, $api_interface, false, (string) ($data['error']['message'] ?? ''), $this->accessToken);
                $this->handleError($data['error'], $data);
            }

            $this->pushCallLog($method, $uri, $api_interface, true, '', $this->accessToken);
            return $data;
        } catch (FacebookApiException $e) {
            // handleError 已记失败；若别处直接抛出则补记
            throw $e;
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response && str_contains($e->getMessage(), 'Calls to this api have exceeded the rate limit')) {
                logging(['token' => $this->accessToken, 'headers' => $response->getHeaders()], 'limit-exceeded', 'limit');
            }

            if ($response) {
                $this->captureAppUsage($response, $this->accessToken, $this->busineId, $this->platformId);
                $nowBody = json_decode((string) $response->getBody(), true) ?: [];
                $payload = [...$nowBody, 'token' => $this->accessToken];
            } else {
                $nowBody = [];
                $payload = ['message' => $e->getMessage(), 'token' => $this->accessToken];
            }

            $this->pushCallLog($method, $uri, $api_interface, false, (string) ($nowBody['error']['message'] ?? $e->getMessage()), $this->accessToken);
            $message = json_encode($payload);
            throw new FacebookApiException(
                $message,
                $e->getCode(),
                [],
                $e
            );
        }
    }

    /**
     * FB 响应头 x-app-usage 的全局回调。
     * 进程内共用一份（启动时注册一次），用于按 BM/个号 ID 更新限流水位 / 熔断，不存请求级状态。
     *
     * @var null|callable(array $usage, string $accessToken, int $busineId, int $platformId): void
     */
    protected static $appUsageHandler = null;

    /**
     * FB 接口调用日志回调（启动时注册一次）。
     * 入参含 token / api_interface / is_success / call_hour 等，由业务层写入 Redis 队列。
     *
     * @var null|callable(array $log): void
     */
    protected static $callLogHandler = null;

    /**
     * 注册 x-app-usage 处理回调。
     * 建议仅在应用启动或 Adv 初始化时调用一次；勿在业务请求里反复 set，避免协程间互相覆盖。
     *
     * @param null|callable(array $usage, string $accessToken, int $busineId, int $platformId): void $handler
     */
    public static function setAppUsageHandler(?callable $handler): void
    {
        self::$appUsageHandler = $handler;
    }

    /**
     * 注册接口调用日志回调。
     *
     * @param null|callable(array $log): void $handler
     */
    public static function setCallLogHandler(?callable $handler): void
    {
        self::$callLogHandler = $handler;
    }

    /**
     * 从 FB HTTP 响应中读取 x-app-usage 头并回调业务层。
     * 成功/失败响应都应调用；无该头或未注册 handler 时直接返回。
     */
    protected function captureAppUsage(\Psr\Http\Message\ResponseInterface $response, string $accessToken, int $busineId, int $platformId): void
    {
        $header = $response->getHeaderLine('x-app-usage');
        if ($header === '' || ! is_callable(self::$appUsageHandler)) {
            return;
        }
        $usage = json_decode($header, true);
        if (is_array($usage)) {
            (self::$appUsageHandler)($usage, $accessToken, $busineId, $platformId);
        }
    }

    /**
     * 推送单次调用日志到业务回调（由 App 层处理：OAuth 异常标记 + Redis 队列）。
     * 是否跳过监控入库由业务层 FbApiCallLogService 决定，保证失败也能标记 OAuth。
     */
    protected function pushCallLog(
        string $method,
        string $uri,
        string $apiInterface,
        bool $success,
        string $error = '',
        string $token = '',
    ): void {
        if (! is_callable(self::$callLogHandler)) {
            return;
        }
        $interface = $apiInterface !== ''
            ? $apiInterface
            : (strtoupper($method) . ':' . preg_replace('#^/v\d+\.\d+#', '', $uri));

        if ($error || !$success) {
            logging(['message' => $error, 'method' => $method, 'url' => $uri, 'success' => $success, 'token' => $token], '接口日志', 'call_logs');
        }

        (self::$callLogHandler)([
            'token' => $token,
            'api_interface' => $interface,
            'is_success' => $success ? 1 : 0,
            'error' => $error,
            'call_hour' => date('Y-m-d H:00:00'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 规范化请求路径
     * - 完整 URL：只取 path（已含 /v25.0/...）
     * - 相对路径：补版本号前缀
     */
    protected function normalizeUri(string $uri): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            $parsed = parse_url($uri);
            return $parsed['path'] ?? '/';
        }

        // path 已带版本号（如 FB next 解析后的 /v25.0/xxx）
        if (preg_match('#^/v\d+\.\d+/#', $uri)) {
            return $uri;
        }

        $basePath = parse_url($this->baseUri, PHP_URL_PATH);
        $apiVersion = $basePath ? trim($basePath, '/') : 'v24.0';

        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }
        if (!str_starts_with($uri, '/' . $apiVersion . '/')) {
            $uri = '/' . $apiVersion . $uri;
        }

        return $uri;
    }

    /**
     * 解析 paging.next 完整 URL，返回 [path, query]
     */
    protected function parsePagingNextUrl(string $nextUrl): array
    {
        $parsed = parse_url($nextUrl);
        $path = $parsed['path'] ?? '/';
        $query = [];
        if (!empty($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        // next URL 已含 access_token 时去掉，统一用 client token
        unset($query['access_token']);

        return [$path, $query];
    }

    protected function handleError(array $error, array $raw): void
    {
        $code = $error['code'] ?? 0;
        $message = $error['message'] ?? 'Facebook API error';

        // 190 = access token 失效
        if ($code === 190) {
            throw new FacebookTokenExpiredException(
                $message,
                $code,
                $raw
            );
        }

        throw new FacebookApiException(
            $message,
            $code,
            $raw
        );
    }

    /* ========= 分页（核心能力） ========= */

    /**
     * Facebook Cursor / next URL 分页
     * 返回 Generator，流式消费
     */
    public function paginate(
        string $uri,
        array $query = [],
        string $api_interface = '',
        int $max = 100000
    ): \Generator {
        $next = $uri;
        $params = $query;
        $count = 0;

        while ($next) {
            // 所以我们需要确保路径包含版本号：/v24.0/act_xxx/insights
            if (str_starts_with($next, 'http://') || str_starts_with($next, 'https://')) {
                [$next, $params] = $this->parsePagingNextUrl($next);
            }
            $response = $this->get($next, $params, $api_interface);
            
            // 确保有数据字段
            if (!isset($response['data'])) {
                break;
            }

            foreach ($response['data'] as $item) {
                yield $item;

                if (++$count >= $max) {
                    return;
                }
            }

            // Facebook 的 next 是完整 URL
            $next = $response['paging']['next'] ?? null;
            // 后续页参数都在 next URL 里
            $params = [];
        }
    }

    /**
     * 一次性拉全（不推荐大数据量）
     */
    public function getAll(string $uri, array $query = [], string $api_interface = ''): array
    {
        return iterator_to_array(
            $this->paginate($uri, $query, $api_interface)
        );
    }
}