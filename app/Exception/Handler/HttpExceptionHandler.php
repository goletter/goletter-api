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
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Exception\MethodNotAllowedHttpException;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class HttpExceptionHandler extends ExceptionHandler
{
    use JsonResponseTrait;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        /** @var HttpException $throwable */
        $status = $throwable->getStatusCode();

        // 路由未匹配时框架抛出的 NotFoundHttpException 没有 message，主动抛出的保留原文
        $message = match (true) {
            $throwable instanceof NotFoundHttpException => $throwable->getMessage() ?: '路由不存在',
            $throwable instanceof MethodNotAllowedHttpException => '请求方法不允许',
            default => $throwable->getMessage() ?: 'HTTP 错误',
        };

        return $this->json($response, $status, $status, $message);
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof HttpException;
    }
}
