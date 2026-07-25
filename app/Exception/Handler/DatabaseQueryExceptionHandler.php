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

namespace App\Exception\Handler;

use App\Constants\LogTypeConstant;
use Hyperf\Database\Exception\QueryException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Logger\Logger;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class DatabaseQueryExceptionHandler extends ExceptionHandler
{
    use Exception;
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 这里可以记录日志，发送报警等
        logging([
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString(),
        ], '服务器错误', LogTypeConstant::Daily, Logger::ERROR);

        // 阻止异常冒泡
        $this->stopPropagation();
        $data = json_encode([
            'code' => $throwable->getCode(),
            'message' => '服务端开小差了！',
        ], JSON_UNESCAPED_UNICODE);

        return $this->response(500, $data, $response);
    }

    public function isValid(Throwable $throwable): bool
    {
        // 判断异常是否是 QueryException 实例
        return $throwable instanceof QueryException;
    }
}
