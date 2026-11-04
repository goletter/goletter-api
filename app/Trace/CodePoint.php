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

use Hyperf\Context\Context;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\Handler;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Throwable;

/**
 * 代码位置解析：HTTP 入口（server）、CLI 命令（command）、出站调用（locate）、异常抛出点（exception）。
 *
 * 路由 preamble（取当前请求 → 取 Dispatched → 拿 Handler）与回调形态判断
 * 在本文件各只有一份实现（routedHandler / normalizeCallback），其余方法一律复用；
 * TraceMiddleware 的 routePattern 亦复用 routedHandler（Task 5）。
 */
final class CodePoint
{
    /**
     * HTTP 入口的代码位置：路由回调（控制器方法）定义的第一行。取不到返回 ['', 0]。
     *
     * @return array{0: string, 1: int} [file, line]
     */
    public static function server(ServerRequestInterface $request): array
    {
        $target = self::routeTarget($request);
        if ($target === null) {
            return ['', 0];
        }

        try {
            $reflection = new ReflectionMethod($target[0], $target[1]);
        } catch (ReflectionException) {
            // 路由回调指向不存在的类/方法：当作"取不到位置"，不打断请求。
            return ['', 0];
        }

        return [$reflection->getFileName() ?: '', $reflection->getStartLine() ?: 0];
    }

    /**
     * CLI 命令的代码位置：命令类的定义行。
     *
     * @return array{0: string, 1: int}
     */
    public static function command(string $class): array
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException) {
            return ['', 0];
        }

        return [$reflection->getFileName() ?: '', $reflection->getStartLine() ?: 0];
    }

    /**
     * 从调用栈里定位第一个业务帧（跳过 vendor 与 app/Trace|Middleware|Log|Http）。
     * 用于出站 HTTP 慢调用（按开关记录）；异常用 exception()。
     *
     * @return array{0: string, 1: int}
     */
    public static function locate(int $limit = 32): array
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $limit) as $frame) {
            $file = $frame['file'] ?? '';
            if ($file === '' || self::isInfrastructure($file)) {
                continue;
            }

            return [$file, (int) ($frame['line'] ?? 0)];
        }

        return ['', 0];
    }

    /**
     * 异常抛出点（无条件记录，与 TRACE_CODE_POINT_* 开关无关）。
     *
     * @return array{0: string, 1: int}
     */
    public static function exception(Throwable $throwable): array
    {
        return [$throwable->getFile(), $throwable->getLine()];
    }

    /**
     * 路由解析共享入口（TraceMiddleware::routePattern 亦用）。
     *
     * 优先取当前协程 Context 里的请求（框架在分发时写入），退回传入的请求；
     * 拿到路由命中的 Handler 才返回，未命中/未分发返回 null。
     */
    public static function routedHandler(ServerRequestInterface $request): ?Handler
    {
        $routed = Context::get(ServerRequestInterface::class);
        $candidate = $routed instanceof ServerRequestInterface ? $routed : $request;
        $dispatched = $candidate->getAttribute(Dispatched::class);
        if (! $dispatched instanceof Dispatched || ! $dispatched->handler instanceof Handler) {
            return null;
        }

        return $dispatched->handler;
    }

    /**
     * 路径规范化唯一处：空 path（PSR-7 允许）按 '/' 记，避免日志里出现空串。
     */
    public static function path(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();

        return $path === '' ? '/' : $path;
    }

    /** 路由回调 → [class, method]；未命中路由时 null。 */
    private static function routeTarget(ServerRequestInterface $request): ?array
    {
        $handler = self::routedHandler($request);

        return $handler === null ? null : self::normalizeCallback($handler->callback);
    }

    /**
     * 回调形态判断唯一处：数组 [class|object, method] 与 'Class@method' 字符串两种写法。
     * 其他形态（闭包、不可识别的值）返回 null。
     *
     * @return null|array{0: string, 1: string}
     */
    private static function normalizeCallback(mixed $callback): ?array
    {
        if (is_array($callback) && isset($callback[0], $callback[1]) && is_string($callback[1])) {
            $class = is_object($callback[0]) ? $callback[0]::class : (string) $callback[0];

            return [$class, $callback[1]];
        }
        if (is_string($callback) && str_contains($callback, '@')) {
            return explode('@', $callback, 2);
        }

        return null;
    }

    private static function isInfrastructure(string $file): bool
    {
        foreach ([
            BASE_PATH . '/vendor/',
            BASE_PATH . '/app/Trace/',
            BASE_PATH . '/app/Middleware/',
            BASE_PATH . '/app/Log/',
            // app/Http/ 是基础设施：locate() 从 OutboundTraceMiddleware 里调用时，
            // 自己的帧必须跳过，否则代码位置永远指回中间件自身。
            BASE_PATH . '/app/Http/',
        ] as $prefix) {
            if (str_starts_with($file, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
