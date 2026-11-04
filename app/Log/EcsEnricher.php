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
use Hyperf\Coroutine\Coroutine;
use Monolog\LogRecord;

/**
 * ECS 环境字段（§6.2）：event.kind / event.category、service.*、host.*、process.pid、
 * coroutine.id。log.level 不在这里——由 EcsJsonFormatter 从 record 等级派生。
 * 每行日志现读配置：这些键是启动期固化的静态值，读取成本可忽略，且不缓存以免挡住将来热更。
 */
final class EcsEnricher
{
    /** §6.2 channel → event.category；app（及未知频道）省略 */
    private const CATEGORIES = [
        Channels::HTTP => 'web',
        Channels::QUEUE => 'process',
        Channels::CLI => 'process',
        Channels::SQL => 'database',
    ];

    public function __construct(private ConfigInterface $config)
    {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $service = (string) $this->config->get('trace.otel.service_name', 'hyperf-base-service');
        $extra = [
            'event.kind' => 'event',
            'service.name' => $service,
            // 日志只保留 service.environment（APP_ENV）；发版号走 OTLP 资源 OTEL_SERVICE_VERSION，避免与 environment 重复。
            'service.environment' => (string) $this->config->get('app_env', 'dev'),
            'service.module' => ((string) $this->config->get('trace.otel.module', '')) ?: $service,
            'host.name' => (string) $this->config->get('trace.otel.host_name', ''),
            'host.ip' => (string) $this->config->get('trace.otel.host_ip', ''),
            'process.pid' => (int) getmypid(),
            'coroutine.id' => Coroutine::id(),
        ];
        $category = self::CATEGORIES[$record->channel] ?? null;
        if ($category !== null) {
            $extra['event.category'] = $category;
        }

        return $record->with(extra: array_merge($record->extra, $extra));
    }
}
