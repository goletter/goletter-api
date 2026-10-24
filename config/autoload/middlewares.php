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
        // 仅拦截 /api/admin/*，普通 API 不鉴权
        App\Middleware\AuthMiddleWare::class,
    ],
];

