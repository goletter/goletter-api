<?php

declare(strict_types=1);

namespace App\Listener;

use App\Trace\Otel\OtelBootstrap;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\Framework\Event\OnWorkerExit;

/**
 * 启动期装配 OTel TracerProvider（否则 Globals 为 Noop，日志无 trace.id）。
 * Worker 退出时 flush/shutdown，避免 BatchSpanProcessor 队列残留。
 *
 * 仅在 config/autoload/listeners.php 注册，避免与 #[Listener] 双重绑定。
 */
class OtelBootstrapListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            BootApplication::class,
            OnWorkerExit::class,
        ];
    }

    public function process(object $event): void
    {
        if ($event instanceof BootApplication) {
            OtelBootstrap::boot();

            return;
        }

        if ($event instanceof OnWorkerExit) {
            OtelBootstrap::flush();
            OtelBootstrap::shutdown();
        }
    }
}
