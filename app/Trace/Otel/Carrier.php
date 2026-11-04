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

namespace App\Trace\Otel;

use App\Trace\TraceHeaders;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\SpanContext;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\ContextInterface;
use Psr\Http\Message\RequestInterface;

/**
 * 跨进程链路头的唯一出入口（W3C traceparent 注入/提取 + 旧头兜底）。
 *
 * 兜底规则（迁移期）：没有有效 traceparent 但带旧 X-Trace-Id（UUID）时，
 * 去横线当 32 位 traceId 续上同一条 trace；旧 X-Span-Id 是 UUID，凑不出合法
 * 16 位 spanId，随机生成一个（它只作"接链"用，不会出现在新链路的父子关系里）。
 */
final class Carrier
{
    /** 注入到 header 数组（队列消息头 / 任意数组载体）。inject 的 carrier 是引用传参，原地修改后返回。 */
    public static function inject(array $headers): array
    {
        TraceContextPropagator::getInstance()->inject($headers);

        return $headers;
    }

    /** 出站 PSR-7 请求：注入 traceparent（默认 getter/setter 兼容 PSR-7 getHeaders() 的数组结构）。 */
    public static function injectRequest(RequestInterface $request): RequestInterface
    {
        $headers = $request->getHeaders();
        TraceContextPropagator::getInstance()->inject($headers);
        foreach ($headers as $name => $values) {
            $request = $request->withHeader($name, $values);
        }

        return $request;
    }

    /**
     * 从 header 提取父上下文（队列消息头数组 / HTTP 请求头数组均可）。
     * PSR-7 风格的头值是 string[]，先拍平成 string 再交给 W3C 传播器（它只认字符串值）。
     * 无有效链路时返回当前上下文（= 无父，新开一条 trace）。
     *
     * 边界：若调用时当前上下文已有活跃 Span，isValid() 会短路返回当前上下文、不再走旧头兜底
     * （当前调用点都在干净上下文上，行为不受影响）。
     */
    public static function extract(array $headers): ContextInterface
    {
        $flat = [];
        foreach ($headers as $key => $value) {
            $flat[$key] = is_array($value) ? (string) ($value[0] ?? '') : (string) $value;
        }

        // 不显式传父上下文：extract() 第 2 个参数是 getter 不是 Context，
        // 缺省即取 Context::getCurrent()（vendor TraceContextPropagator::extract 内实现）。
        $context = TraceContextPropagator::getInstance()->extract($flat);
        if (Span::fromContext($context)->getContext()->isValid()) {
            return $context;
        }

        // 旧头名刻意写成字面量、不绑命名来源常量：这是迁移期旧系统的 wire 字面量契约，
        // 旧常量已随自建追踪清退，这里只认 wire 上的字面量。
        $traceId = self::normalizeHexId(TraceHeaders::headerValue($flat, 'x-trace-id'), 32);
        if ($traceId === null) {
            return $context;
        }

        $spanId = self::normalizeHexId(TraceHeaders::headerValue($flat, 'x-span-id'), 16) ?? bin2hex(random_bytes(8));
        $remote = SpanContext::createFromRemoteParent($traceId, $spanId, TraceFlags::SAMPLED);

        return Span::wrap($remote)->storeInContext($context);
    }

    public static function currentTraceId(): string
    {
        return Span::getCurrent()->getContext()->getTraceId();
    }

    public static function currentSpanId(): string
    {
        return Span::getCurrent()->getContext()->getSpanId();
    }

    private static function normalizeHexId(?string $raw, int $length): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $hex = strtolower(str_replace('-', '', trim($raw)));
        if (strlen($hex) !== $length || ! ctype_xdigit($hex) || trim($hex, '0') === '') {
            return null;
        }

        return $hex;
    }
}
