<?php

declare(strict_types=1);

use App\Controller\Tiktok\TiktokController;
use Goletter\Server\Router\Router;

Router::addGroup('/api/tiktok', function () {

    Router::addRoute(
        ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
        '/{resource}[/{seg1}[/{seg2}]]',
        [TiktokController::class, 'handle']
    );
});
