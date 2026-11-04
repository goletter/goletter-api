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

namespace App\Log;

/**
 * 执行耗时（ECS event.duration_ms）的唯一算法。
 *
 * 语义：从各通道「边界入口」的 microtime(true) 到**写该条日志前一刻**的墙钟毫秒数（保留两位小数）。
 * - HTTP 入站：TraceMiddleware 入口 → http.access（RequestEntryClock）
 * - HTTP 出站：Client 发出请求前 → http.outbound
 * - CLI：BeforeHandle → cli.command
 * - 队列：consumeMessage / publish 入口 → 对应 queue_* 日志
 * - SQL：驱动报告的语句执行时间（非应用入口墙钟，见 DbQueryExecutedListener）
 */
final class EventDuration
{
    /** 自边界入口 $startedAt（microtime(true)）到调用时刻。 */
    public static function ms(float $startedAt): float
    {
        return round((microtime(true) - $startedAt) * 1000, 2);
    }

    /**
     * @return array{event.duration_ms: float}
     */
    public static function field(float $startedAt): array
    {
        return ['event.duration_ms' => self::ms($startedAt)];
    }
}
