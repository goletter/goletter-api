<?php
declare(strict_types=1);

use Goletter\Server\Router\Router;

Router::addGroup('/api/admin', function () {
    Router::get('/index', [\App\Controller\Admin\IndexController::class, 'index']);
    Router::get('/accounts', [\App\Controller\Admin\AccountController::class, 'index']);
    // Router::post('/mtls/client-certificates', [\App\Controller\MtlsCertificateController::class, 'store']);
}, ['middleware' => [App\Middleware\AuthMiddleWare::class, Goletter\Server\Middleware\TenantMiddleware::class]]);