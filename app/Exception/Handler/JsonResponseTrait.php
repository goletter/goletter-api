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

use Goletter\Observability\Log\Log;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function Hyperf\Support\env;

trait JsonResponseTrait
{
    protected function json(ResponseInterface $response, int $status, int $code, string $message, array $extra = []): ResponseInterface
    {
        $data = json_encode(array_merge(['code' => $code, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);

        // 异常不会再经过 CorsMiddleware，这里需要自行补跨域头
        return $response->withStatus($status)
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, X-Tenant-Id')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream($data));
    }

    protected function isDebug(): bool
    {
        return in_array(env('APP_ENV', 'dev'), ['dev', 'local', 'test'], true);
    }

    protected function logThrowable(Throwable $throwable, string $title): void
    {
        Log::http()->error('http', [
            get_class($throwable),
            $throwable->getMessage(),
            $throwable->getFile() . ':' . $throwable->getLine(),
            $throwable->getTraceAsString(),
            $title,
        ]);
    }
}
