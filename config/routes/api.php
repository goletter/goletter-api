<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use App\Controller\GoogleController;
use App\Controller\IndexController;
use App\Controller\TencentController;
use Goletter\Server\Router\Router;

Router::addGroup('/api', function () {
    Router::get('/index', [IndexController::class, 'index']);

    Router::get('/google/auth-url', [GoogleController::class, 'authUrl']);
    Router::get('/google/callback', [GoogleController::class, 'callback']);

    Router::get('/tencent/auth-url', [TencentController::class, 'authUrl']);
    Router::get('/tencent/callback', [TencentController::class, 'callback']);
});
