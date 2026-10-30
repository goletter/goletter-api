<?php

declare(strict_types=1);

use function Hyperf\Support\env;

/**
 * 出站 Webhook（应用侧）。
 *
 * 租户动态配置在平台端，本模块通过 PlatformClient::getWebhookConfig 拉取；
 * 无平台配置时回落到下方全局默认。
 *
 * URL 解析优先级：
 * 1. account_demands.extend.callback_url / webhook_url
 * 2. 平台租户 events[event] / url
 * 3. webhook.events.{event} / webhook.default_url
 */
return [
    /** 无平台租户配置时的全局开关；有平台配置时以平台 enable 为准 */
    'enable' => (bool) env('WEBHOOK_ENABLE', false),

    'secret' => (string) env('WEBHOOK_SECRET', ''),

    'timeout' => (float) env('WEBHOOK_HTTP_TIMEOUT', 5.0),
    'connect_timeout' => (float) env('WEBHOOK_HTTP_CONNECT_TIMEOUT', 2.0),

    'signature_header' => (string) env('WEBHOOK_SIGNATURE_HEADER', 'X-Goletter-Signature'),

    /** 平台租户配置缓存秒数；0 = 不缓存 */
    'tenant_cache_ttl' => (int) env('WEBHOOK_TENANT_CACHE_TTL', 60),

    /**
     * 推送失败后重投等待毫秒数（全局默认）。
     * 平台返回的 retry_delay_ms 优先；0 = 立即重试。
     */
    'retry_delay_ms' => (int) env('WEBHOOK_RETRY_DELAY_MS', 3000),

    /** 失败最多执行次数（含首次）；超过进死信 */
    'max_attempts' => (int) env('WEBHOOK_MAX_ATTEMPTS', 5),

    'default_url' => (string) env('WEBHOOK_DEFAULT_URL', ''),

    'events' => [
        'account_demand.succeeded' => (string) env('WEBHOOK_ACCOUNT_DEMAND_SUCCEEDED_URL', ''),
        'account_demand.failed' => (string) env('WEBHOOK_ACCOUNT_DEMAND_FAILED_URL', ''),
    ],
];
