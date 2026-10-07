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

/**
 * 兜底异常处理器，必须注册在最后.
 */
class DefaultExceptionHandler extends ExceptionHandler
{
    use JsonResponseTrait;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logThrowable($throwable, 'DefaultException', LogTypeConstant::Daily, Logger::ERROR);

        $this->stopPropagation();

        if ($this->isDebug()) {
            return $this->json($response, 500, 500, $throwable->getMessage(), [
                'exception' => get_class($throwable),
                'file' => $throwable->getFile() . ':' . $throwable->getLine(),
            ]);
        }

        return $this->json($response, 500, 500, '服务器错误');
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
