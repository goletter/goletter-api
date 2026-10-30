<?php

declare(strict_types=1);

use function Hyperf\Support\env;

/**
 * goletter/hyperf-webhook 通用配置。
 *
 * 业务事件 URL（如 account_demand.*）请在应用侧 config/autoload/webhook.php 的 events 中扩展。
 */
return [
    'enable' => (bool) env('WEBHOOK_ENABLE', false),

    'secret' => (string) env('WEBHOOK_SECRET', ''),

    'timeout' => (float) env('WEBHOOK_HTTP_TIMEOUT', 5.0),
    'connect_timeout' => (float) env('WEBHOOK_HTTP_CONNECT_TIMEOUT', 2.0),

    /** 默认失败重推间隔毫秒；应用可按租户覆盖 */
    'retry_delay_ms' => (int) env('WEBHOOK_RETRY_DELAY_MS', 3000),
    'max_attempts' => (int) env('WEBHOOK_MAX_ATTEMPTS', 5),

    /** 签名头，值为 sha256=<hex> */
    'signature_header' => (string) env('WEBHOOK_SIGNATURE_HEADER', 'X-Goletter-Signature'),

    /** 未配置事件级 URL 时的默认回调地址 */
    'default_url' => (string) env('WEBHOOK_DEFAULT_URL', ''),

    /**
     * 按事件覆盖 URL；空字符串表示回落到 default_url。
     *
     * @var array<string, string>
     */
    'events' => [],
];
