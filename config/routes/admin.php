<?php

declare(strict_types=1);

use App\Controller\Admin\ApiController;
use Goletter\Server\Router\Router;

Router::addGroup('/api/admin', function () {
    // 公开接口等静态路由写在通配符之前
    // Router::post('/login', [...]);

    Router::addRoute(
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        '/{resource}[/{seg1}[/{seg2}]]',
        [ApiController::class, 'handle']
    );
});
