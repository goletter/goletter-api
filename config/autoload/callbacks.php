<?php

declare(strict_types=1);

use App\Callback\Handler\DemoPingHandler;

/**
 * 第三方入站回调：事件名 => Handler。
 * 加事件 = 本文件加一行 + 新建 Handler，不必改 Dispatcher / 其它服务。
 */
return [
    'handlers' => [
        'ping' => DemoPingHandler::class,
    ],
];
