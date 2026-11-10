<?php

declare(strict_types=1);

use App\Controller\Akm\AkmController;
use Goletter\Server\Router\Router;

Router::addGroup('/api/akm', function () {

    Router::addRoute(
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        '/{resource}[/{seg1}[/{seg2}]]',
        [AkmController::class, 'handle']
    );
});
