<?php

declare(strict_types=1);

use function Hyperf\Support\env;

/**
 * 子模块调用平台端鉴权配置。
 *
 * 其它项目访问本模块时，PlatformAuthMiddleware 携带 Token/应用凭证
 * 请求平台 verify 接口，通过后写入 tenant / user 上下文。
 */
return [
    // false 时中间件直接放行（本地联调可关）
    'enable' => (bool) env('PLATFORM_AUTH_ENABLE', true),

    // 当前子模块标识（平台侧用于判断是否开通）
    'module' => env('PLATFORM_MODULE', 'goletter-api'),

    // 平台基址，例如 https://platform.example.com
    'base_uri' => rtrim((string) env('PLATFORM_BASE_URI', ''), '/'),

    // 平台校验接口路径
    'verify_path' => env('PLATFORM_VERIFY_PATH', '/internal/auth/verify'),

    // 子模块调平台的服务凭证（平台校验调用方身份）
    'app_id' => env('PLATFORM_APP_ID', ''),
    'app_secret' => env('PLATFORM_APP_SECRET', ''),

    'timeout' => (float) env('PLATFORM_HTTP_TIMEOUT', 3.0),
    'connect_timeout' => (float) env('PLATFORM_HTTP_CONNECT_TIMEOUT', 1.0),

    // 校验结果缓存秒数；0 = 不缓存
    'cache_ttl' => (int) env('PLATFORM_AUTH_CACHE_TTL', 60),

    // 需要走平台鉴权的路径前缀
    'only' => [
        '/api',
    ],

    // 公开接口（不走平台）
    'except' => [
        '/api/ping',
        '/api/admin*', // Admin 仍由 AuthMiddleWare 处理
    ],
];
