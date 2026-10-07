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
return [
    'handler' => [
        'http' => [
            App\Exception\Handler\HttpExceptionHandler::class,
            App\Exception\Handler\ValidationExceptionHandler::class,
            App\Exception\Handler\AppExceptionHandler::class,
            App\Exception\Handler\DatabaseQueryExceptionHandler::class,
            // isValid 恒为 true，必须放在最后
            App\Exception\Handler\DefaultExceptionHandler::class,
        ],
    ],
];
