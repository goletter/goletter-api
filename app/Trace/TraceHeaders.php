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

use Psr\Http\Message\ResponseInterface;

/**
 * 追踪头和跨域放行名单的唯一来源。
 * 浏览器预检、异常响应、正常响应都从这里合并，避免各写一份后漏头。
 */
final class TraceHeaders
{
    public const REQUEST = 'X-Request-Id';

    public const TRACE = 'X-Trace-Id';

    public const BROWSER = 'X-Browser-Id';

    public const TAB = 'X-Tab-Id';

    /** 前端应用版本。纯后端不生成，空值不写进日志和出站头。 */
    public const VERSION = 'X-Api-Version';

    /** 租户标识（SaaS 维度）。入口读取、出站与消息头透传；业务代码也可在鉴权后写入 BusinessContext。 */
    public const TENANT = 'X-Tenant-Id';

    public const BROWSER_COOKIE = 'browser_id';

    /** @var list<string> */
    public const ALLOW = [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'Accept',
        'X-Tenant-Id',
        'X-Browser-Id',
        'X-Tab-Id',
        'X-Request-Id',
        'X-Trace-Id',
        'X-Api-Version',
    ];

    /** @var list<string> */
    public const EXPOSE = [
        'X-Request-Id',
        'X-Trace-Id',
    ];

    /**
     * 版本号不是 UUID。只接受短的可见字符，换行和路径符号直接丢掉。
     */
    public static function version(string $value): string
    {
        $value = trim($value);
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{0,31}\z/', $value) !== 1) {
            return '';
        }

        return $value;
    }

    /**
     * 大小写不敏感地取一个头值（数组载体 / PSR-7 getHeaders() 的 string[] 值都收）。
     * 值是数组时取第一个元素——PSR-7 的头值就是 string[]；非标量（嵌套表等）一律当没带。
     */
    public static function headerValue(array $headers, string $name): ?string
    {
        foreach ($headers as $key => $value) {
            if (strcasecmp((string) $key, $name) !== 0) {
                continue;
            }
            if (is_array($value)) {
                $value = $value[0] ?? null;
            }

            return is_scalar($value) ? (string) $value : null;
        }

        return null;
    }

    public static function applyCors(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withHeader(
                'Access-Control-Allow-Headers',
                self::merge($response->getHeaderLine('Access-Control-Allow-Headers'), self::ALLOW)
            )
            ->withHeader(
                'Access-Control-Expose-Headers',
                self::merge($response->getHeaderLine('Access-Control-Expose-Headers'), self::EXPOSE)
            );
    }

    /**
     * @param list<string> $required
     */
    public static function merge(string $existing, array $required): string
    {
        $merged = [];
        $seen = [];
        foreach (array_merge(self::split($existing), $required) as $header) {
            $name = trim($header);
            if ($name === '') {
                continue;
            }
            $key = strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $merged[] = $name;
        }

        return implode(', ', $merged);
    }

    /**
     * @return list<string>
     */
    private static function split(string $header): array
    {
        if ($header === '') {
            return [];
        }

        return explode(',', $header);
    }
}
