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

use Hyperf\Context\ApplicationContext;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * 日志助手：一行拿通道、直接写 PSR-3。业务代码与 demo 的默认姿势。
 *
 *   Log::app()->info('order_paid', ['order_id' => 1]);     // → runtime/logs/app/YYYY-MM-DD.log
 *   Log::queue()->info('settle_done', ['order_id' => 1]);  // → runtime/logs/queue/YYYY-MM-DD.log
 *
 * 通道即方法名（白名单见 Channels）；其他已登记通道用 Log::channel(Channels::XXX)。
 *
 * 链路串联零代码：config/autoload/logger.php 给所有通道挂了 TraceContextProcessor，每条记录
 * 自动附加 trace.id / span.id / span.parent_id（无父 Span 时为 ""）/ request_id / tenant.id——HTTP、队列、
 * 命令行、事件监听全链路同一个 trace.id。因此这里不提供任何「传链路 ID」的参数，也不要手动往
 * 字段里塞这些 ID（会覆盖 processor 写入的同名 extra，两边值不一致时排障会被误导）。
 *
 * 性能：内部就是 LoggerFactory::get()（实例按通道缓存，不新建 handler、不重开文件），
 * 每次调用只是一次容器寻址 + 数组取；写盘走 Swoole 文件 hook（bin/hyperf.php 默认
 * SWOOLE_HOOK_ALL），fopen/fwrite/flock 由协程调度——慢盘只挂住当前协程，不阻塞其他协程；
 * 不做内存缓冲，崩溃零丢失（要提吞吐优先调 LOG_LEVEL，而不是加缓冲）。
 *
 * 耗时统一用 ECS `event.duration_ms`（见 EventDuration）；各通道在写日志时自行带上。
 *
 * 字段名红线：不要用保留键——`message`（事件名占用）、`params`、`response`、`error`、
 * `request_id`、`tenant.id` 等信封/链路字段（全表见 EcsJsonFormatter::RESERVED_ORDER 与
 * TraceContextProcessor）。同名 context 会被 formatter 静默丢弃，字段凭空消失就是这么来的。
 *
 * 什么时候不用本助手：长生命周期组件（构造注入的 Listener/中间件/Service）优先构造注入
 * LoggerFactory——省掉每次容器寻址，也便于测试时注入替身（见 README「四、手动写日志」）。
 */
final class Log
{
    private function __construct()
    {
    }

    /** 业务代码（demo 默认通道）。 */
    public static function app(): LoggerInterface
    {
        return self::channel(Channels::APP);
    }

    /** HTTP 访问日志、出站调用。 */
    public static function http(): LoggerInterface
    {
        return self::channel(Channels::HTTP);
    }

    /** 队列投递与消费。 */
    public static function queue(): LoggerInterface
    {
        return self::channel(Channels::QUEUE);
    }

    /** 命令行、定时任务。 */
    public static function cli(): LoggerInterface
    {
        return self::channel(Channels::CLI);
    }

    /** 数据库（量大单独放，避免冲淡业务）。 */
    public static function sql(): LoggerInterface
    {
        return self::channel(Channels::SQL);
    }

    /**
     * 任意已登记通道（用 Channels 常量，不要裸字符串拼目录名——
     * 未登记的名字会被 logger.php 兜底落到 app/ 目录）。
     */
    public static function channel(string $name): LoggerInterface
    {
        // 容器未初始化时 ApplicationContext 直接抛 TypeError（返回类型不可空），
        // 这里没有可恢复的降级动作，不再包一层异常。
        return ApplicationContext::getContainer()
            ->get(LoggerFactory::class)
            ->get($name);
    }
}
