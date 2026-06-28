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
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Logger\Logger;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class DefaultExceptionHandler extends ExceptionHandler
{
    use Exception;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $res = [
            'code' => $throwable->getCode(),
            'message' => $throwable->getMessage(),
        ];
        // 格式化输出
        $data = json_encode($res, JSON_UNESCAPED_UNICODE);
        // 记录错误日志
        logging([
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
            $throwable->getTraceAsString(),
        ], 'DefaultException', LogTypeConstant::Daily, Logger::ERROR);

        // 阻止异常冒泡
        $this->stopPropagation();
        return $this->response(422, $data, $response);
    }

    // 判断该异常类是否要对该异常进行处理
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
