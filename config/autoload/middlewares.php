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
        Goletter\Server\Middleware\RequestDecryptMiddleware::class,
        Goletter\Server\Middleware\HeaderMiddleware::class,
        Goletter\Server\Middleware\LocaleMiddleware::class,
        Goletter\Server\Middleware\CorsMiddleware::class,
        Goletter\Server\Middleware\ResponseFormatMiddleware::class,
        Goletter\Server\Middleware\ModelBindingMiddleware::class,
    ],
];
