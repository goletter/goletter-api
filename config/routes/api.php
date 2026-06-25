<?php
declare(strict_types=1);

use Goletter\Server\Router\Router;

Router::addGroup('/api', function () {
    Router::post('/index', [\App\Controller\IndexController::class, 'index']);
});