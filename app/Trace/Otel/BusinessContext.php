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
use Hyperf\Context\Context;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * 请求态业务字段（ECS §6.2 的 [自定义] 段）存 Hyperf Context —— 每请求/每消息一协程，
 * 天然隔离。写入键名白名单固定（KEYS），拼错直接抛异常，防日志字段悄悄漂移。
 */
final class BusinessContext
{
    /** 白名单 = 日志契约里允许从业务侧注入的字段全集 */
    public const KEYS = ['request_id', 'browser.id', 'tab.id', 'http.api_version', 'tenant.id', 'company.id', 'user.id'];

    private const PREFIX = 'trace.business.';

    public static function set(string $key, int|string|null $value): void
    {
        if (! in_array($key, self::KEYS, true)) {
            throw new InvalidArgumentException(sprintf('未登记的业务字段: %s（允许: %s）', $key, implode(', ', self::KEYS)));
        }
        if ($value === null || $value === '') {
            return;   // 空值不落（§6.2：空字符串不输出）
        }
        Context::set(self::PREFIX . $key, (string) $value);
    }

    public static function get(string $key): ?string
    {
        $value = Context::get(self::PREFIX . $key);

        return $value === null ? null : (string) $value;
    }

    /** @return array<string, string> 只含已设置的键 */
    public static function all(): array
    {
        $fields = [];
        foreach (self::KEYS as $key) {
            $value = self::get($key);
            if ($value !== null && $value !== '') {
                $fields[$key] = $value;
            }
        }

        return $fields;
    }

    /** @param array<string, null|int|string> $fields */
    public static function merge(array $fields): void
    {
        foreach ($fields as $key => $value) {
            self::set($key, $value);
        }
    }

    /**
     * HTTP 入口：请求头 → 业务字段。X-Request-Id 缺失时生成（响应头只回显请求里带了的，日志始终有）。
     * company.id / user.id 不从请求头读：它们是本服务鉴权后由业务代码 set() 的内部身份。
     */
    public static function fromRequest(ServerRequestInterface $request): void
    {
        self::set('request_id', $request->getHeaderLine(TraceHeaders::REQUEST) ?: bin2hex(random_bytes(16)));
        self::set('browser.id', $request->getHeaderLine(TraceHeaders::BROWSER) ?: self::cookieBrowserId($request));
        self::set('tab.id', $request->getHeaderLine(TraceHeaders::TAB));
        self::set('http.api_version', TraceHeaders::version($request->getHeaderLine(TraceHeaders::VERSION)));
        self::set('tenant.id', $request->getHeaderLine(TraceHeaders::TENANT));
    }

    public static function clear(): void
    {
        foreach (self::KEYS as $key) {
            Context::destroy(self::PREFIX . $key);
        }
    }

    /** 旧行为保留：前端编号允许放在 browser_id cookie 里（头优先，cookie 兜底）。 */
    private static function cookieBrowserId(ServerRequestInterface $request): string
    {
        $value = $request->getCookieParams()[TraceHeaders::BROWSER_COOKIE] ?? '';

        return is_string($value) ? $value : '';
    }
}
