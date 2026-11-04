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

use Hyperf\Context\Context;
use OpenTelemetry\API\Trace\Span;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\SDK\Trace\Span as SdkSpan;

/**
 * 日志侧 trace.* 字段的唯一入口（§6.2）。
 *
 * 设计偏离（相对 spec §5.1 的"parent_span_id 栈"）：不用栈，父 span 直接问 SDK Span
 * 的 getParentContext()——更准（remote parent 也能看到），且少一份跨协程状态。
 * trace.id 与 trace.flags 永远同进同出（§6.5 约束三）。
 */
final class TraceScope
{
    private const SNAPSHOT_KEY = 'trace.scope.snapshot';

    /** @return array<string, string> 无活动 Span 时返回 [] */
    public static function currentTraceFields(): array
    {
        $context = Span::getCurrent()->getContext();
        if (! $context->isValid()) {
            return [];
        }

        return [
            'trace.id' => $context->getTraceId(),
            'span.id' => $context->getSpanId(),
            // 根 Span 无父时也输出空字符串，便于运维/Loki 固定列（与 params/response 同策略）。
            'span.parent_id' => self::parentSpanId(),
            'trace.flags' => ($context->getTraceFlags() & TraceFlags::SAMPLED) !== 0 ? '01' : '00',
        ];
    }

    /**
     * 把当前字段快照进协程（TraceMiddleware 在 Span 结束前调用）。
     * 异常处理器、defer 回调等"Span 之后"的代码拿不到活动 Span 与业务上下文，只能读快照。
     *
     * @param array<string, string> $fields
     */
    public static function remember(array $fields): void
    {
        Context::set(self::SNAPSHOT_KEY, $fields);
    }

    /** @return array<string, string> 没快照（或已被 forget）时返回 [] */
    public static function remembered(): array
    {
        $fields = Context::get(self::SNAPSHOT_KEY, []);

        return is_array($fields) ? $fields : [];
    }

    /** 清快照。协程结束自动销毁；非协程环境（cid=-1，部分单测）是进程级静态存储，测试收尾必须手动清。 */
    public static function forget(): void
    {
        Context::destroy(self::SNAPSHOT_KEY);
    }

    private static function parentSpanId(): string
    {
        $span = Span::getCurrent();
        if (! $span instanceof SdkSpan) {
            // NonRecordingSpan（remote parent 直接作为 current）没有父上下文可言。
            return '';
        }
        $parent = $span->getParentContext();

        return $parent->isValid() ? $parent->getSpanId() : '';
    }
}
