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

use App\Trace\CodePoint;
use App\Trace\RequestEntryClock;
use App\Trace\Otel\BusinessContext;
use App\Trace\Otel\Carrier;
use App\Trace\Otel\TraceScope;
use App\Trace\TraceHeaders;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\SDK\Trace\Span as SdkSpan;
use OpenTelemetry\SemConv\Attributes\ClientAttributes;
use OpenTelemetry\SemConv\Attributes\CodeAttributes;
use OpenTelemetry\SemConv\Attributes\ErrorAttributes;
use OpenTelemetry\SemConv\Attributes\HttpAttributes;
use OpenTelemetry\SemConv\Attributes\NetworkAttributes;
use OpenTelemetry\SemConv\Attributes\ServerAttributes;
use OpenTelemetry\SemConv\Attributes\UrlAttributes;
use OpenTelemetry\SemConv\Attributes\UserAgentAttributes;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

/**
 * HTTP 入口：建立 SERVER Span + 业务上下文，出口写响应头。
 *
 * 全站第一个中间件（config/autoload/middlewares.php 首位），跑在路由之前：
 * 起始 Span 名只能先用路径占位；handler 返回后（finally）再用已落定的路由模板改名并补
 * http.route / 代码位置。4xx 不算失败；5xx 与异常 setStatus(ERROR)。
 */
class TraceMiddleware implements MiddlewareInterface
{
    private const string TRACER_NAME = 'app';

    public function __construct(private ConfigInterface $config)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        RequestEntryClock::mark();
        BusinessContext::fromRequest($request);

        $span = Globals::tracerProvider()
            ->getTracer(self::TRACER_NAME)
            ->spanBuilder(self::route($request))
            ->setSpanKind(SpanKind::KIND_SERVER)
            ->setParent(Carrier::extract($request->getHeaders()))
            ->setAttributes($this->requestAttributes($request))
            ->startSpan();
        $scope = $span->activate();

        $status = 500;
        $failed = false;
        try {
            $response = $handler->handle($request);
            $status = $response->getStatusCode();

            return $this->applyResponseHeaders($response, $span);
        } catch (Throwable $exception) {
            if ($exception instanceof HttpException && $exception->getStatusCode() < 500) {
                // 4xx 是客户端错误（Hyperf 的 404/422 等以 HttpException 形式抛出）：按真实响应码记录，
                // 不设 ERROR、不记异常事件——与无异常 4xx 响应路径一致对待（spec §6.8）。
                $status = $exception->getStatusCode();
            } else {
                $failed = true;
                $span->setAttribute(ErrorAttributes::ERROR_TYPE, $exception::class);
                $span->recordException($exception);
                $span->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
            }

            throw $exception;
        } finally {
            $span->setAttribute(HttpAttributes::HTTP_RESPONSE_STATUS_CODE, $status);
            if ($status >= 500 && ! $failed) {
                // 5xx 而无异常：sem-conv 约定 error.type 取状态码字符串（spec §5.1）。
                $span->setAttribute(ErrorAttributes::ERROR_TYPE, (string) $status);
                $span->setStatus(StatusCode::STATUS_ERROR);
            }
            $this->finalizeSpan($span, $request);
            // 异常处理器与 defer 回调都跑在 Span 结束之后（拿不到活动 Span 和业务上下文）：
            // 收尾前把字段快照进协程，供 TraceContextProcessor 与异常响应头兜底。
            TraceScope::remember(TraceScope::currentTraceFields() + BusinessContext::all());
            $scope->detach();
            $span->end();
            BusinessContext::clear();
            RequestEntryClock::forget();
        }
    }

    /**
     * Span 名与访问日志 http.route 的同一出处：{METHOD} {路由模板}；路由未落定时退化为 {METHOD} {路径}。
     * 本中间件跑在路由前，入口拿到的是路径；AccessLogMiddleware（Task 11 复用）在路由后调用，拿到模板。
     */
    public static function route(ServerRequestInterface $request): string
    {
        return strtoupper($request->getMethod()) . ' ' . (self::routePattern($request) ?? CodePoint::path($request));
    }

    /**
     * 已落定的路由模板（Hyperf CoreMiddleware 把路由后的请求写进 Context），取不到返回 null。
     * 路由解析委托 CodePoint::routedHandler（唯一 preamble）。
     */
    public static function routePattern(ServerRequestInterface $request): ?string
    {
        return CodePoint::routedHandler($request)?->route;
    }

    /** @return array<string, string|int> */
    private function requestAttributes(ServerRequestInterface $request): array
    {
        $attributes = [
            HttpAttributes::HTTP_REQUEST_METHOD => $request->getMethod(),
            UrlAttributes::URL_PATH => CodePoint::path($request),
            UrlAttributes::URL_SCHEME => $request->getUri()->getScheme() ?: 'http',
            NetworkAttributes::NETWORK_PROTOCOL_VERSION => $request->getProtocolVersion(),
        ];

        // server.address / server.port 从 Host 头解析（spec §5.1）。
        $host = $request->getHeaderLine('Host');
        if ($host !== '') {
            [$serverAddress, $serverPort] = array_pad(explode(':', $host, 2), 2, null);
            if ($serverAddress !== '') {
                $attributes[ServerAttributes::SERVER_ADDRESS] = $serverAddress;
            }
            if (is_string($serverPort) && ctype_digit($serverPort)) {
                $attributes[ServerAttributes::SERVER_PORT] = (int) $serverPort;
            }
        }

        $userAgent = $request->getHeaderLine('User-Agent');
        if ($userAgent !== '') {
            $attributes[UserAgentAttributes::USER_AGENT_ORIGINAL] = $userAgent;
        }

        // 与 AccessLogMiddleware::ip() 同口径：只认 server params，不信任 X-Forwarded-For（由网关层负责）。
        $server = $request->getServerParams();
        $clientIp = $server['remote_addr'] ?? $server['REMOTE_ADDR'] ?? '';
        if (is_string($clientIp) && $clientIp !== '') {
            $attributes[ClientAttributes::CLIENT_ADDRESS] = $clientIp;
        }

        return $attributes;
    }

    /** handler 返回后路由才落定：补 http.route、代码位置，并把 Span 名升级为路由模板。 */
    private function finalizeSpan(SpanInterface $span, ServerRequestInterface $request): void
    {
        $route = self::routePattern($request);
        if ($route !== null) {
            $span->setAttribute(HttpAttributes::HTTP_ROUTE, $route);
            // SpanInterface::updateName 存在（API 层，NonRecordingSpan 为 no-op）；守卫为防御性写法（brief 指定保留）。
            if ($span instanceof SdkSpan) {
                // 复用 route()，与入口命名同一出处。
                $span->updateName(self::route($request));
            }
        }

        if ($this->config->get('trace.otel.code_point_server', true)) {
            [$file, $line] = CodePoint::server($request);
            if ($file !== '') {
                $span->setAttribute(CodeAttributes::CODE_FILE_PATH, $file);
                $span->setAttribute(CodeAttributes::CODE_LINE_NUMBER, $line);
            }
        }
    }

    private function applyResponseHeaders(ResponseInterface $response, SpanInterface $span): ResponseInterface
    {
        $response = TraceHeaders::applyCors($response);
        if ($span->getContext()->isValid()) {
            $response = $response->withHeader(TraceHeaders::TRACE, $span->getContext()->getTraceId());
        }

        // X-Request-Id 恒回（spec §4.2「前端无感」）：客户端带过回显其值，否则回服务端生成的（同进日志）。
        $requestId = BusinessContext::get('request_id');
        if ($requestId !== null && $requestId !== '') {
            $response = $response->withHeader(TraceHeaders::REQUEST, $requestId);
        }

        return $response;
    }
}
