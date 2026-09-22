<?php

declare(strict_types=1);

use App\Middleware\TenantMiddleware;
use Goletter\Server\Router\Router;

Router::addGroup('/api', function () {
    // 探活可不带租户（TenantMiddleware::$except 已放行部分路径；此处 index 也可自行调整）
    Router::get('/index', [App\Controller\IndexController::class, 'index']);
}, ['middleware' => [TenantMiddleware::class]]);
