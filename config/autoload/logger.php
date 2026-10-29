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

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

use function Hyperf\Support\env;

/**
 * 按分类写到 {LOG_PATH}/{category}/YYYY-MM-DD.log
 *
 * LoggerFactory::get($name) / logging(..., $category) 的 name/category 即目录名。
 */
$logBase = static function (): string {
    return rtrim((string) (env('LOG_PATH') ?: BASE_PATH . '/runtime/logs'), '/');
};

$normalizeCategory = static function (string $name): string {
    $category = strtolower(trim($name));
    $category = (string) preg_replace('/[^a-z0-9_\-]+/', '', $category);

    return $category !== '' ? $category : 'hyperf';
};

$ensureDir = static function (string $dir): void {
    if (is_dir($dir)) {
        return;
    }
    if (! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
        throw new RuntimeException('无法创建日志目录：' . $dir);
    }
};

$level = static function () {
    $raw = strtoupper((string) env('LOG_LEVEL', 'DEBUG'));
    $map = [
        'DEBUG' => Logger::DEBUG,
        'INFO' => Logger::INFO,
        'NOTICE' => Logger::NOTICE,
        'WARNING' => Logger::WARNING,
        'ERROR' => Logger::ERROR,
        'CRITICAL' => Logger::CRITICAL,
        'ALERT' => Logger::ALERT,
        'EMERGENCY' => Logger::EMERGENCY,
    ];

    return $map[$raw] ?? Logger::DEBUG;
};

$lineFormat = "%datetime%||%channel%||%level_name%||%message%||%context%\n";

/**
 * @return array{handler: array, formatter: array, processors: array}
 */
$rotatingCategory = static function (string $name) use ($logBase, $normalizeCategory, $ensureDir, $level, $lineFormat): array {
    $category = $normalizeCategory($name);
    $dir = $logBase() . '/' . $category;
    $ensureDir($dir);

    return [
        'handler' => [
            'class' => RotatingFileHandler::class,
            'constructor' => [
                // filename 仅提供目录与扩展名；filenameFormat={date} => YYYY-MM-DD.log
                'filename' => $dir . '/app.log',
                'maxFiles' => (int) env('LOG_MAX_FILES', 30),
                'level' => $level(),
                'dateFormat' => 'Y-m-d',
                'filenameFormat' => '{date}',
            ],
        ],
        'formatter' => [
            'class' => LineFormatter::class,
            'constructor' => [
                'format' => $lineFormat,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
                'ignoreEmptyContextAndExtra' => true,
            ],
        ],
        'processors' => [],
    ];
};

return [
    // logging($data, $msg, 'queue') / LoggerFactory::get('sql') 等走这里
    'default' => $rotatingCategory,

    // docs 包可设 DOCS_LOG_GROUP=docs；独立目录 runtime/logs/docs/
    'docs' => static function (string $name) use ($rotatingCategory) {
        return $rotatingCategory($name !== '' ? $name : 'docs');
    },
];
