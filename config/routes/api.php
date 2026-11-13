<?php

declare(strict_types=1);

use App\Controller\Api\ApiController;
use Goletter\Server\Router\Router;

Router::addGroup('/api', function () {

    Router::addRoute(
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        '/{resource}[/{seg1}[/{seg2}]]',
        [ApiController::class, 'handle']
    );
});
