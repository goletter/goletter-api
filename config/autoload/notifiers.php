<?php

declare(strict_types=1);

use App\Notifier\Handler\ModelWebhookHandler;

/**
 * 业务出站通知：事件名 => Handler。
 * Handler 内组载荷并调用 WebhookNotifier；HTTP/重试/队列仍由 NotifyWebhook 负责。
 * 加事件 = 本文件加一行（或新建 Handler），业务侧统一 OutboundDispatcher::emit。
 */
return [
    'handlers' => [
        'account_demand.succeeded' => ModelWebhookHandler::class,
        'account_demand.failed' => ModelWebhookHandler::class,
    ],
];
