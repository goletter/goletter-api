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

use Monolog\LogRecord;

use function Hyperf\Config\config;

/**
 * 按键名脱敏。位置参数（SQL bindings 的 0、1、2）无法判断是不是密钥，所以默认不记录 bindings。
 */
final class RedactLogProcessor
{
    /** @var list<string> */
    public const DEFAULT_KEYS = [
        'password',
        'passwd',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'cookie',
        'secret',
        'api_key',
        'apikey',
        'cvv',
        'pan',
        'card_number',
        'id_no',
        'private_key',
        'set-cookie',
    ];

    /**
     * @param null|list<string> $keys 测试可传入；生产留空，从 trace.redact_keys 读取
     */
    public function __construct(private ?array $keys = null)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $keys = $this->resolveKeys();

        return $record->with(
            context: $this->redact($record->context, $keys),
            extra: $this->redact($record->extra, $keys),
        );
    }

    /**
     * @return array<string, true>
     */
    private function resolveKeys(): array
    {
        $configured = $this->keys;
        if ($configured === null) {
            $value = config('trace.redact_keys');
            $configured = is_array($value) && $value !== [] ? $value : self::DEFAULT_KEYS;
        }

        $normalized = [];
        foreach ($configured as $key) {
            $normalized[self::normalize((string) $key)] = true;
        }

        return $normalized;
    }

    /**
     * @param array<mixed> $data
     * @param array<string, true> $keys
     * @return array<mixed>
     */
    private function redact(array $data, array $keys): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && isset($keys[self::normalize($key)])) {
                $result[$key] = '***';
                continue;
            }
            $result[$key] = is_array($value) ? $this->redact($value, $keys) : $value;
        }

        return $result;
    }

    /**
     * 与 TraceBoundaryListener 的命令行值级脱敏共用，口径必须一致（public 供其直接调用，避免重复实现）。
     */
    public static function normalize(string $key): string
    {
        return strtolower(str_replace(['_', '-'], '', $key));
    }
}
