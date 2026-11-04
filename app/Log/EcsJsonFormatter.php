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

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use Monolog\Formatter\FormatterInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Stringable;
use UnitEnum;

use function Hyperf\Config\config;

/**
 * ECS 8.11 日志格式（spec §6.1）：单行 JSON、下划线平铺、保留键固定键序、无数组字段。
 *
 * 信封字段（@timestamp / ecs_version / schema_version / log_level / log_logger / message）
 * 由本类从 LogRecord 派生，context / extra 里的同名键一律忽略（跳过，不覆盖）。
 * 等级是 LogRecord 的一等属性：ErrorNormalizer 用 with(level:) 调整它，这里只做名称映射。
 * 写入侧仍可用点号键（如 trace.id）；落盘前统一转成下划线（trace_id）。
 */
class EcsJsonFormatter implements FormatterInterface
{
    public const string ECS_VERSION = '8.11';

    public const string SCHEMA_VERSION = '1.0';

    /** 通用字段表顺序：保留键按此序在前，其余键按字典序在后。 */
    private const array RESERVED_ORDER = [
        '@timestamp',
        'ecs_version',
        'schema_version',
        'log_level',
        'log_logger',
        'message',
        'event_category',
        'event_kind',
        'event_outcome',
        'event_duration_ms',
        'trace_id',
        'span_id',
        'span_parent_id',
        'trace_flags',
        'request_id',
        'service_name',
        'service_environment',
        'service_module',
        'host_name',
        'host_ip',
        'process_pid',
        'coroutine_id',
        'tenant_id',
        'company_id',
        'user_id',
        'browser_id',
        'tab_id',
        'params',
        'response',
        'error',
    ];

    private string $precision;

    /**
     * @param null|string $precision ms / us；留空读 trace.log.timestamp_precision（logger.php 不传参数）
     */
    public function __construct(?string $precision = null)
    {
        $precision ??= (string) config('trace.log.timestamp_precision', 'ms');
        $this->precision = $precision === 'us' ? 'us' : 'ms';
    }

    public function format(LogRecord $record): string
    {
        $envelope = [
            '@timestamp' => $this->timestamp($record->datetime),
            'ecs_version' => self::ECS_VERSION,
            'schema_version' => self::SCHEMA_VERSION,
            'log_level' => self::levelName($record->level),
            'log_logger' => $record->channel,
            'message' => $record->message,
        ];

        $fields = $envelope;
        // context 与 extra 同键冲突时 context 优先（array_merge 后者覆盖前者）
        foreach (array_merge($record->extra, $record->context) as $key => $value) {
            $key = self::underscoreKey((string) $key);
            if (array_key_exists($key, $envelope)) {
                continue;   // 信封字段不可覆盖
            }
            $value = self::normalize($value);
            if ($value === null || ($value === '' && ! in_array($key, ['params', 'response', 'span_parent_id'], true))) {
                continue;   // 空值不输出（契约字段保留 "" 便于固定列）
            }
            $fields[$key] = $value;
        }

        return json_encode(
            self::order($fields),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
        ) . "\n";
    }

    /** 点号平铺 → 下划线（trace.id → trace_id）；已是下划线的保持不变。 */
    private static function underscoreKey(string $key): string
    {
        return str_replace('.', '_', $key);
    }

    public function formatBatch(array $records): string
    {
        $output = '';
        foreach ($records as $record) {
            $output .= $this->format($record);
        }

        return $output;
    }

    private function timestamp(DateTimeInterface $datetime): string
    {
        // Monolog 的 datetime 用 PHP 默认时区创建，契约要求 UTC，必须显式转换
        $utc = DateTimeImmutable::createFromInterface($datetime)->setTimezone(new DateTimeZone('UTC'));

        return $utc->format($this->precision === 'us' ? 'Y-m-d\TH:i:s.u\Z' : 'Y-m-d\TH:i:s.v\Z');
    }

    /**
     * §6.1：Monolog 的 toPsrLogLevel() 给的是 warning，契约要 warn，必须自建映射。
     */
    private static function levelName(Level $level): string
    {
        return match ($level) {
            Level::Debug => 'debug',
            Level::Info => 'info',
            Level::Notice => 'notice',
            Level::Warning => 'warn',
            Level::Error => 'error',
            Level::Critical => 'critical',
            Level::Alert => 'alert',
            Level::Emergency => 'emergency',
        };
    }

    /**
     * 数组禁令（§6.1）的兜底，递归生效：list 数组编码为字符串，assoc 数组保留为对象
     * （params / response / error 三类合法结构化对象），其余对象降级为标量。
     * 正常调用方不该踩到这些分支——兜底只保证"写日志本身绝不抛异常"。
     */
    private static function normalize(mixed $value): mixed
    {
        if ($value instanceof JsonSerializable) {
            return self::normalize($value->jsonSerialize());
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format(DateTimeInterface::RFC3339);
        }
        if ($value instanceof UnitEnum) {
            return $value instanceof BackedEnum ? $value->value : $value->name;
        }
        if ($value instanceof Stringable) {   // 含 Throwable
            return (string) $value;
        }
        if (is_array($value)) {
            if ($value === []) {
                return null;
            }
            if (array_is_list($value)) {
                return json_encode(
                    $value,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
                );
            }
            $normalized = [];
            foreach ($value as $key => $item) {
                $item = self::normalize($item);
                if ($item === null) {
                    continue;
                }
                $normalized[(string) $key] = $item;
            }

            return $normalized;
        }
        if (is_object($value)) {
            return $value::class;
        }
        if (is_float($value) && ! is_finite($value)) {
            return (string) $value;   // INF / NAN 不能进 JSON
        }

        return $value;
    }

    private static function order(array $fields): array
    {
        $ordered = [];
        foreach (self::RESERVED_ORDER as $key) {
            if (array_key_exists($key, $fields)) {
                $ordered[$key] = $fields[$key];
            }
        }
        $rest = array_diff_key($fields, $ordered);
        ksort($rest, SORT_STRING);

        return $ordered + $rest;
    }
}
