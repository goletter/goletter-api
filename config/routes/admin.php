<?php
declare(strict_types=1);

use Goletter\Server\Router\Router;

Router::addGroup('/api/admin', function () {
    //
}, ['middleware' => [App\Middleware\AuthMiddleWare::class]]);