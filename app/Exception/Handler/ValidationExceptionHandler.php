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
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    use JsonResponseTrait;

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        if ($throwable instanceof ValidationException) {
            $errors = $throwable->errors();
            $first = reset($errors);
            $message = is_array($first) ? (string) ($first[0] ?? '') : '';

            return $this->json($response, 422, 422, $message ?: '参数错误', ['errors' => $errors]);
        }

        return $this->json($response, 422, 422, $throwable->getMessage() ?: '参数错误');
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidateException || $throwable instanceof ValidationException;
    }
}
