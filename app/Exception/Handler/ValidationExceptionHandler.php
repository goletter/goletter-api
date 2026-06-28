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

use App\Exception\ValidateException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Collection\Arr;
use Hyperf\Collection\Collection;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    use Exception;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $message = $throwable->getMessage();
        if ($throwable instanceof ValidationException) {
            $message = Arr::get((new Collection($throwable->errors()))->first(), 0, '');
        }
        // 阻止异常冒泡
        $this->stopPropagation();
        $data = json_encode([
            'code' => $throwable->getCode(),
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE);

        return $this->response(422, $data, $response);
    }

    /**
     * @param Throwable $throwable 抛出的异常
     * @return bool 该异常处理器是否处理该异常
     */
    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidateException || $throwable instanceof ValidationException;
    }
}
