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

use Hyperf\Contract\ConfigInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

/**
 * 错误日志统一出口（§6.5）：exception → error.{type,message,code,stack_trace} 展开，
 * 并强制一致性约束——① error 非空 ⇒ 至少 warn（低于升到 error）且 outcome=failure；
 * ② level ≥ error 且无 error 对象 ⇒ 补 outcome=failure（不伪造 error 对象）。
 * 必须排在 Processor 链最后：它要看最终的 error 有无与 level。
 */
final class ErrorNormalizer
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $context = $record->context;
        $extra = $record->extra;
        $exception = $context['exception'] ?? $extra['exception'] ?? null;
        unset($context['exception'], $extra['exception']);

        $error = $this->error($exception, $context['error'] ?? $extra['error'] ?? null);
        $level = $record->level;
        $outcome = $context['event.outcome'] ?? null;

        if ($error !== null) {
            $context['error'] = $error;
            $outcome = 'failure';                      // 约束一：error 非空 ⇒ outcome=failure
            if ($level->value < Level::Warning->value) {
                $level = Level::Error;                 // 约束一：至少 warn，低于则升到 error
            }
        } elseif ($level->value >= Level::Error->value && $outcome === null) {
            $outcome = 'failure';                      // 约束二：error 级日志补 outcome（spec 原文写 level=error，按语义扩到 critical 及以上）
        }

        if ($outcome !== null) {
            $context['event.outcome'] = $outcome;
        }

        return $record->with(level: $level, context: $context, extra: $extra);
    }

    /**
     * @return null|array<string, string> 无错误信息时 null（error 键不落）
     */
    private function error(mixed $exception, mixed $existing): ?array
    {
        $error = [];
        if (is_array($existing)) {
            foreach ($existing as $key => $value) {
                if ($value !== null && $value !== '') {
                    $error[(string) $key] = $value;
                }
            }
        }

        if ($exception instanceof Throwable) {
            $error['type'] = $exception::class;
            $error['message'] = $exception->getMessage();
            $code = (string) $exception->getCode();
            if ($code !== '' && $code !== '0') {
                $error['code'] = $code;
            }
            $stack = $this->stackTrace($exception);
            if ($stack !== null) {
                $error['stack_trace'] = $stack;
            }
        } elseif (is_string($exception) && $exception !== '') {
            $error['type'] = $exception;   // 过渡态：旧写法 'exception' => $throwable::class
        }

        return $error === [] ? null : $error;
    }

    /**
     * §6.5：换行分隔字符串（数组禁令），#0 是抛点；行数上限含 #0；关闭时整字段省略。
     */
    private function stackTrace(Throwable $throwable): ?string
    {
        if (! (bool) $this->config->get('trace.log.stacktrace', true)) {
            return null;
        }

        $limit = max(1, (int) $this->config->get('trace.log.stacktrace_frames', 50));
        $lines = ['#0 ' . $throwable->getFile() . ':' . $throwable->getLine()];
        $index = 1;
        foreach ($throwable->getTrace() as $frame) {
            if ($index >= $limit) {
                break;
            }
            $file = $frame['file'] ?? '';
            if ($file === '') {
                continue;   // internal 帧没有文件位置，跳过
            }
            $lines[] = '#' . $index . ' ' . $file . ':' . ($frame['line'] ?? 0);
            ++$index;
        }

        return implode("\n", $lines);
    }
}
