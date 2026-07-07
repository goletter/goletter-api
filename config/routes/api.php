<?php
declare(strict_types=1);

use Goletter\Server\Router\Router;

Router::addGroup('/api', function () {
    Router::get('/index', [\App\Controller\IndexController::class, 'index']);

    Router::get('/google/auth-url', [\App\Controller\GoogleController::class, 'index']);
    Router::get('/google/callback', [\App\Controller\GoogleController::class, 'index']);
});