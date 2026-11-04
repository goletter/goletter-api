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

namespace App\Trace;

use App\Log\EventDuration;
use Hyperf\Context\Context;

/**
 * HTTP 请求入口时刻（TraceMiddleware 第一行），供 http.access 的 event.duration_ms：
 * 从进站到写访问日志为止，与 SERVER Span 边界一致。
 */
final class RequestEntryClock
{
    private const KEY = 'trace.http.entry_time';

    public static function mark(): void
    {
        Context::set(self::KEY, microtime(true));
    }

    public static function forget(): void
    {
        Context::destroy(self::KEY);
    }

    /** 入口至今毫秒数；未 mark 时 null。 */
    public static function elapsedMs(): ?float
    {
        $started = Context::get(self::KEY);
        if (! is_float($started)) {
            return null;
        }

        return EventDuration::ms($started);
    }
}
