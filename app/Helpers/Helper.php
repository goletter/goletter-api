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

use Hyperf\Context\Context;
use Hyperf\Logger\Logger;
use App\Constants\LogTypeConstant;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * 通用文件日志：runtime/logs/{category}/Y-m-d.log
 *
 * @param array $data 上下文（写入 Monolog context）
 */
function logging(
    array $data,
    string $msg = '调试',
    string $category = LogTypeConstant::Daily,
    int $level = Logger::INFO
): void {
    $traceId = (string) Context::get('trace_id', '');
    if ($traceId !== '') {
        $data['trace_id'] = $traceId;
    }

    $dir = strtolower($category);
    $log = new Logger('goletter');
    $dateFormat = 'Y-m-d H:i:s';
    $stream = new StreamHandler(BASE_PATH . '/runtime/logs/' . $dir . '/' . date('Y-m-d') . '.log', $level);
    $output = "%datetime%||%channel%||%level_name%||%message%||%context%\n";
    $formatter = new LineFormatter($output, $dateFormat);
    $stream->setFormatter($formatter);
    $log->pushHandler($stream);
    $log->log($level, $msg, $data);
}