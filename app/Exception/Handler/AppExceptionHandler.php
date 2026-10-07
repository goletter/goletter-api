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

use Goletter\Resource\Exception\BusinessException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 业务异常：属于正常业务分支，不记录日志.
 */
class AppExceptionHandler extends ExceptionHandler
{
    use JsonResponseTrait;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        $code = $throwable->getCode();
        // 业务码落在 4xx 时同步为 HTTP 状态码（如 401 未登录、403 无权限），其余返回 200
        $status = $code >= 400 && $code < 500 ? $code : 200;

        return $this->json($response, $status, $code, $throwable->getMessage());
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof BusinessException;
    }
}
