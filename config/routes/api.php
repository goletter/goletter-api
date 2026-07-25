<?php
declare(strict_types=1);

use Goletter\Mtls\Middleware\ClientCertificateMiddleware;
use Goletter\Server\Router\Router;

Router::addGroup('/api', function () {
    Router::get('/index', [\App\Controller\IndexController::class, 'index']);

    Router::get('/google/auth-url', [\App\Controller\GoogleController::class, 'authUrl']);
    Router::get('/google/callback', [\App\Controller\GoogleController::class, 'callback']);
}, ['middleware' => [ClientCertificateMiddleware::class]]);

Router::addGroup('/api', function () {
    Router::post('/test', [\App\Controller\IndexController::class, 'test']);
});