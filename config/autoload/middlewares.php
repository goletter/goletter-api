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
return [
    'http' => [
        Goletter\Server\Middleware\RateLimitMiddleware::class,
        Goletter\Server\Middleware\TraceGatewayMiddleware::class,
        Goletter\Server\Middleware\CorsMiddleware::class,
        Goletter\Server\Middleware\RequestDecryptMiddleware::class,
        Goletter\Server\Middleware\HeaderMiddleware::class,
        Goletter\Server\Middleware\LocaleMiddleware::class,
        Goletter\Server\Middleware\ResponseFormatMiddleware::class,
        Goletter\Server\Middleware\IdempotencyMiddleware::class,
        Goletter\Server\Middleware\ModelBindingMiddleware::class,
        // 其它项目访问本子模块：向平台校验 Token / 应用凭证
        App\Middleware\PlatformAuthMiddleware::class,
        // 本模块 Admin：/api/admin/*
        App\Middleware\AuthMiddleWare::class,
    ],
];

