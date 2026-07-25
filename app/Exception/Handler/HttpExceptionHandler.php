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

use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Exception\MethodNotAllowedHttpException;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * HTTP请求异常处理器
 * Class HttpExceptionHandler.
 */
class HttpExceptionHandler extends ExceptionHandler
{
    use Exception;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 判断是否是路由存在
        $data = json_encode([
            'code' => $throwable->getStatusCode(),
            'message' => '路由不存在!',
        ], JSON_UNESCAPED_UNICODE);

        // 阻止异常冒泡
        $this->stopPropagation();
        return $this->response(500, $data, $response);
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof NotFoundHttpException || $throwable instanceof MethodNotAllowedHttpException;
    }
}
