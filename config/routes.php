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
use Hyperf\HttpServer\Router\Router;

// 加载 Http 路由
Router::addServer('http', function () {
    require __DIR__ . '/routes/facebook.php';
    require __DIR__ . '/routes/tiktok.php';
    require __DIR__ . '/routes/googledoc.php';
    require __DIR__ . '/routes/akm.php';
});