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

use App\Trace\Otel\BusinessContext;
use App\Trace\Otel\TraceScope;
use Monolog\LogRecord;

/**
 * 把当前协程的链路与业务字段注入日志（§6.2）：trace.id / span.id / span.parent_id /
 * trace.flags 来自活跃 Span（无 Span 时整组省略，§6.2 约定），request_id / tenant.id
 * 等来自 BusinessContext。写入 extra——context 留给调用方（§6.1 context 优先）。
 */
final class TraceContextProcessor
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $fields = TraceScope::currentTraceFields() + BusinessContext::all();
        if ($fields === []) {
            // Span 已结束的收尾日志（异常处理器、defer 回调）读中间件留下的快照。
            $fields = TraceScope::remembered();
        }
        if ($fields === []) {
            return $record;
        }

        if (isset($fields['trace.id']) && ! array_key_exists('span.parent_id', $fields)) {
            $fields['span.parent_id'] = '';
        }

        return $record->with(extra: $fields + $record->extra);
    }
}
