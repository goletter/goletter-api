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
    'default' => [
        'handler' => [
            // 按天切分：{LOG_PATH}/hyperf-2026-10-07.log，maxFiles 为保留天数（0 表示不清理）
            'class' => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => rtrim((string) (Hyperf\Support\env('LOG_PATH') ?: BASE_PATH . '/runtime/logs'), '/') . '/hyperf.log',
                'maxFiles' => (int) Hyperf\Support\env('LOG_MAX_FILES', 30),
                'level' => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];
