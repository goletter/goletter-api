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
    use JsonResponseTrait;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logThrowable($throwable, 'DatabaseQueryException', LogTypeConstant::Daily, Logger::ERROR);

        $this->stopPropagation();

        // SQL 可能包含表结构和数据，仅开发环境返回
        $message = $this->isDebug() ? $throwable->getMessage() : '服务端开小差了！';

        return $this->json($response, 500, 500, $message);
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof QueryException;
    }
}
