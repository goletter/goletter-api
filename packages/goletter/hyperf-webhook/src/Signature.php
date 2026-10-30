<?php

declare(strict_types=1);

namespace Goletter\Webhook;

/**
 * Webhook HMAC-SHA256 签名与校验。
 *
 * 约定头值格式：sha256=<hex>
 */
final class Signature
{
    public const PREFIX = 'sha256=';

    public static function sign(string $payload, string $secret): string
    {
        return self::PREFIX . hash_hmac('sha256', $payload, $secret);
    }

    public static function verify(string $payload, string $secret, string $headerValue): bool
    {
        if ($secret === '' || $headerValue === '') {
            return false;
        }

        $expected = self::sign($payload, $secret);

        return hash_equals($expected, trim($headerValue));
    }

    /**
     * 从 "sha256=xxx" 或裸 hex 中取出摘要部分（仅用于调试展示）。
     */
    public static function stripPrefix(string $headerValue): string
    {
        $headerValue = trim($headerValue);
        if (str_starts_with(strtolower($headerValue), self::PREFIX)) {
            return substr($headerValue, strlen(self::PREFIX));
        }

        return $headerValue;
    }
}
