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
use function Hyperf\Support\env;

return [
    // $name 来自 LoggerFactory::get($name)，logging() 的第三个参数 category 会作为 $name
    // 路径：{LOG_PATH}/{name}/YYYY-MM-DD.log
    'default' => static function (string $name) {
        $base = rtrim((string) (env('LOG_PATH') ?: BASE_PATH . '/runtime/logs'), '/');
        $category = strtolower($name !== '' ? $name : 'hyperf');
        $dir = $base . '/' . $category;

        return [
            'handler' => [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    // filename 仅提供目录与扩展名；filenameFormat={date} => YYYY-MM-DD.log
                    'filename' => $dir . '/app.log',
                    'maxFiles' => (int) env('LOG_MAX_FILES', 30),
                    'level' => Monolog\Logger::DEBUG,
                    'dateFormat' => 'Y-m-d',
                    'filenameFormat' => '{date}',
                ],
            ],
            'formatter' => [
                'class' => Monolog\Formatter\LineFormatter::class,
                'constructor' => [
                    // 2026-10-05 10:49:02||test||INFO||xxx||["111"]
                    'format' => "%datetime%||%channel%||%level_name%||%message%||%context%\n",
                    'dateFormat' => 'Y-m-d H:i:s',
                    'allowInlineLineBreaks' => true,
                ],
            ],
        ];
    },
];
