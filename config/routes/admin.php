<?php

declare(strict_types=1);

use App\Controller\Admin\AdminController;
use Goletter\Server\Router\Router;

Router::addGroup('/api/admin', function () {

    Router::addRoute(
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        '/{resource}[/{seg1}[/{seg2}]]',
        [AdminController::class, 'handle']
    );
});
