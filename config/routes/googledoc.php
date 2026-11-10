<?php

declare(strict_types=1);

use App\Controller\GoogleDoc\GoogleDocController;
use Goletter\Server\Router\Router;

Router::addGroup('/api/googledoc', function () {

    Router::addRoute(
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        '/{resource}[/{seg1}[/{seg2}]]',
        [GoogleDocController::class, 'handle']
    );
});
