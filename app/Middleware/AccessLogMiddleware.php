<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Middleware;

use App\Log\Channels;
use App\Log\EventDuration;
use App\Log\ExchangePayload;
use App\Trace\CodePoint;
use App\Trace\RequestEntryClock;
use App\Trace\Otel\BusinessContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\Logger\LoggerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 访问日志一行（ECS，§6.4）：http.* / url.path / client.ip / event.duration_ms，
 * 4xx 记 success、5xx 与异常记 failure（§6.8）。异常路径与 TraceMiddleware 同口径：
 * 4xx 的 HttpException 按真实状态码记 success、不记异常事件；其余异常记 500/failure。
 * 链路字段（trace.id 等）由 TraceContextProcessor 从活跃 Span 注入，这里不手写。
 * params / response 与访问行合并（§6.6）；开关关时两字段为 ''，不另写 http.exchange。
 */
class AccessLogMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory, private ConfigInterface $config)
    {
        $this->logger = $loggerFactory->get(Channels::HTTP);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 路径规范化唯一处（CodePoint::path）：skip 检查与访问日志 url.path 共用同一次取值，
        // 与 TraceMiddleware 的 Span 属性 url.path 同口径（空 path 记 '/'）。
        $path = CodePoint::path($request);

        if ($this->skipped($request, $path)) {
            return $handler->handle($request);
        }

        $started = microtime(true);
        try {
            $response = $handler->handle($request);
        } catch (Throwable $throwable) {
            if ($throwable instanceof HttpException && $throwable->getStatusCode() < 500) {
                // 4xx 是客户端错误（Hyperf 的 404/422 等以 HttpException 形式抛出）：按真实响应码记 success、
                // 不记异常事件——与无异常 4xx 响应路径一致对待（spec §6.8，与 TraceMiddleware 同口径）。
                $this->write($request, $path, $throwable->getStatusCode(), $started, null, null);
            } else {
                $this->write($request, $path, 500, $started, null, $throwable);
            }

            throw $throwable;
        }

        $this->write($request, $path, $response->getStatusCode(), $started, $response, null);

        return $response;
    }

    private function skipped(ServerRequestInterface $request, string $path): bool
    {
        $method = strtoupper($request->getMethod());
        if (in_array($method, $this->strings('trace.access.skip_methods', ['OPTIONS']), true)) {
            return true;
        }

        return in_array($path, $this->strings('trace.access.skip_paths', ['/health', '/favicon.ico']), true);
    }

    private function write(
        ServerRequestInterface $request,
        string $path,
        int $status,
        float $started,
        ?ResponseInterface $response,
        ?Throwable $throwable,
    ): void {
        $durationMs = RequestEntryClock::elapsedMs() ?? EventDuration::ms($started);
        $context = [
            'http.request.method' => strtoupper($request->getMethod()),
            'url.path' => $path,
            'http.response.status_code' => $status,
            'event.duration_ms' => $durationMs,
            'http.request.body.bytes' => ExchangePayload::bodyBytes($request),
            'http.response.body.bytes' => $response === null ? 0 : ExchangePayload::bodyBytes($response),
            'client.ip' => $this->ip($request),
            'event.outcome' => $status >= 500 ? 'failure' : 'success',
        ];
        // Span 名与 http.route 的同一出处（Task 5 的静态方法）；未路由时省略。
        $route = TraceMiddleware::routePattern($request);
        if ($route !== null) {
            $context['http.route'] = $route;
        }
        $protocol = $request->getProtocolVersion();
        if ($protocol !== '') {
            $context['network.protocol.version'] = $protocol;
        }
        $userAgent = $request->getHeaderLine('User-Agent');
        if ($userAgent !== '') {
            $context['user_agent.original'] = $userAgent;
        }
        $apiVersion = BusinessContext::get('http.api_version');
        if ($apiVersion !== null && $apiVersion !== '') {
            $context['http.api_version'] = $apiVersion;
        }
        if ($throwable !== null) {
            // 原始 Throwable 交给 ErrorNormalizer 展开（§6.5），这里不预先字符串化。
            $context['exception'] = $throwable;
        }

        $limit = (int) $this->config->get('trace.log.payload_bytes', 8192);
        $payload = (new ExchangePayload(max(1, $limit)))->forAccessLog(
            $request,
            $response,
            $this->logRequestPayload(),
            $this->logResponsePayload(),
        );
        $context['params'] = $payload['params'];
        $context['response'] = $payload['response'];

        // 正常访问用 notice：LOG_LEVEL=notice 时仍落盘；慢请求/5xx 用 warning。
        $slowMs = (float) $this->config->get('trace.access.slow_ms', 1000);
        if ($status >= 500 || $durationMs >= $slowMs) {
            $this->logger->warning('http.access', $context);
        } else {
            $this->logger->notice('http.access', $context);
        }
    }

    private function logRequestPayload(): bool
    {
        return (bool) $this->config->get('trace.access.log_request_payload', true);
    }

    private function logResponsePayload(): bool
    {
        return (bool) $this->config->get('trace.access.log_response_payload', true);
    }

    /**
     * @param list<string> $default
     * @return list<string>
     */
    private function strings(string $key, array $default): array
    {
        $value = $this->config->get($key, $default);
        if (! is_array($value)) {
            return $default;
        }

        return array_values(array_map(static fn ($item): string => (string) $item, $value));
    }

    private function ip(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();
        $ip = $server['remote_addr'] ?? $server['REMOTE_ADDR'] ?? '';

        return is_string($ip) ? $ip : '';
    }
}
