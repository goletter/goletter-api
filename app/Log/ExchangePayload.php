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

namespace App\Log;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * HTTP 交换体（§6.6）：入站 http.access 与出站 http.outbound 共用。
 * params = {query, body}，response 为响应体；超上限整体 {"_truncated": true, "_bytes": N}。
 * 键名脱敏由 RedactLogProcessor 在落盘时做（本类只管结构、截断与标量归一）。
 */
final class ExchangePayload
{
    public function __construct(private int $limit)
    {
    }

    /**
     * @return array{params: mixed, response: mixed}
     */
    public function capture(ServerRequestInterface $request, ?ResponseInterface $response): array
    {
        return $this->forAccessLog($request, $response, true, true);
    }

    /**
     * @return array{params: mixed, response: mixed}
     */
    public function forAccessLog(
        ServerRequestInterface $request,
        ?ResponseInterface $response,
        bool $logParams,
        bool $logResponse,
    ): array {
        return $this->forExchange($request, $response, $logParams, $logResponse);
    }

    /**
     * 入站 / 出站统一口径（Psr RequestInterface + 可选 Response）。
     *
     * @return array{params: mixed, response: mixed}
     */
    public function forExchange(
        RequestInterface $request,
        ?ResponseInterface $response,
        bool $logParams,
        bool $logResponse,
    ): array {
        return [
            'params' => $logParams ? $this->bounded([
                'query' => $this->value($this->queryParams($request)),
                'body' => $this->exchangeRequestBody($request),
            ]) : '',
            'response' => $logResponse
                ? $this->bounded($response === null ? null : $this->message($response))
                : '',
        ];
    }

    /** Content-Length 优先，否则读 body size（与 http.*.body.bytes 一致）。 */
    public static function bodyBytes(MessageInterface $message): int
    {
        $length = $message->getHeaderLine('Content-Length');
        if (is_numeric($length)) {
            return max(0, (int) $length);
        }
        $size = $message->getBody()->getSize();

        return $size === null ? 0 : $size;
    }

    /**
     * 超限整体替换（§6.6）。_bytes 是被丢弃对象的序列化字节数；
     * 请求/响应的权威尺寸在 http.access 行的 http.request.body.bytes / http.response.body.bytes。
     */
    private function bounded(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($encoded === false) {
            return ['_truncated' => true, '_bytes' => 0];
        }
        if (strlen($encoded) > $this->limit) {
            return ['_truncated' => true, '_bytes' => strlen($encoded)];
        }

        return $value;
    }

    /**
     * @return array<mixed>
     */
    private function queryParams(RequestInterface $request): array
    {
        if ($request instanceof ServerRequestInterface) {
            $query = $request->getQueryParams();
            if ($query !== []) {
                return $query;
            }
        }
        $raw = $request->getUri()->getQuery();
        if ($raw === '') {
            return [];
        }
        parse_str($raw, $parsed);

        return is_array($parsed) ? $parsed : [];
    }

    private function exchangeRequestBody(RequestInterface $request): mixed
    {
        if ($request instanceof ServerRequestInterface) {
            $parsed = $request->getParsedBody();
            if ($parsed !== null && $parsed !== []) {
                return $this->value($parsed);
            }
        }

        return $this->message($request);
    }

    /**
     * body 在 Swoole 里就是内存字符串：直接读，尺寸判定统一交给 bounded()
     * （旧实现的 getSize() 提前返回会让 _bytes 口径分裂，去掉）。
     */
    private function message(MessageInterface $message): mixed
    {
        $body = $message->getBody();
        $raw = $body->getContents();
        if ($body->isSeekable()) {
            $body->rewind();
        }
        if ($raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $this->value($decoded);
        }

        return $raw;
    }

    private function value(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 6) {
            return '...';
        }
        if ($value instanceof UploadedFileInterface) {
            return [
                'filename' => $value->getClientFilename(),
                'size' => $value->getSize(),
            ];
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->value($item, $depth + 1);
            }

            return $result;
        }
        if (is_object($value)) {
            return ['type' => $value::class];
        }

        return $value;
    }
}
