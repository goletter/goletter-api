<?php
declare(strict_types=1);

use Goletter\Server\Router\Router;

Router::addGroup('/api/admin', function () {
    Router::get('/index', [\App\Controller\IndexController::class, 'index']);
});