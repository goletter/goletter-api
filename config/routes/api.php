<?php
declare(strict_types=1);

use Goletter\Server\Router\Router;

Router::addGroup('/api', function () {
    Router::get('/index', [\App\Controller\IndexController::class, 'index']);
    Router::post('/test', [\App\Controller\IndexController::class, 'test']);

    Router::get('/google/auth-url', [\App\Controller\GoogleController::class, 'authUrl']);
    Router::get('/google/callback', [\App\Controller\GoogleController::class, 'callback']);
});