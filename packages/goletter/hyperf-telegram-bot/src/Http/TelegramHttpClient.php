<?php

declare(strict_types=1);

namespace Goletter\Telegram\Http;

use Goletter\Telegram\Exceptions\TelegramApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class TelegramHttpClient
{
    public function __construct(
        protected ClientInterface $http,
        protected string $token,
        protected string $baseUri = 'https://api.telegram.org'
    ) {
        $this->baseUri = rtrim($baseUri, '/');
    }

    /**
     * @param array<string, mixed> $params
     * @return mixed Telegram API result 字段
     * @throws bot\src\Exceptions\TelegramApiException
     */
    public function request(string $method, array $params = []): mixed
    {
        $url = sprintf('%s/bot%s/%s', $this->baseUri, $this->token, ltrim($method, '/'));
        $options = $this->buildOptions($params);

        try {
            $response = $this->http->request('POST', $url, $options);
            $body = (string) $response->getBody();
            $decoded = json_decode($body, true);

            if (! is_array($decoded)) {
                throw new TelegramApiException('Invalid JSON response from Telegram Bot API', 0, [
                    'raw' => $body,
                ]);
            }

            if (($decoded['ok'] ?? false) !== true) {
                $description = (string) ($decoded['description'] ?? 'Telegram Bot API error');
                $errorCode = (int) ($decoded['error_code'] ?? 0);

                // Token 无效时 Telegram 常返回 404 Not Found，补充可读提示
                if ($errorCode === 404 || strcasecmp($description, 'Not Found') === 0) {
                    $description = 'Not Found (usually invalid bot token or wrong API path). '
                        . 'Check token format "{bot_id}:{secret}".';
                }

                throw new TelegramApiException($description, $errorCode, $decoded);
            }

            return $decoded['result'] ?? null;
        } catch (TelegramApiException $e) {
            throw $e;
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $raw = $response ? (string) $response->getBody() : $e->getMessage();
            $decoded = is_string($raw) && $raw !== '' ? (json_decode($raw, true) ?: []) : [];

            throw new TelegramApiException(
                (string) ($decoded['description'] ?? $e->getMessage()),
                (int) ($decoded['error_code'] ?? $e->getCode()),
                is_array($decoded) ? $decoded : ['raw' => $raw],
                $e
            );
        } catch (GuzzleException $e) {
            throw new TelegramApiException($e->getMessage(), (int) $e->getCode(), [], $e);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    protected function buildOptions(array $params): array
    {
        if ($this->requiresMultipart($params)) {
            return [
                RequestOptions::MULTIPART => $this->toMultipart($params),
            ];
        }

        return [
            RequestOptions::HEADERS => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            RequestOptions::JSON => $params,
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function requiresMultipart(array $params): bool
    {
        foreach ($params as $value) {
            if ($this->isUpload($value)) {
                return true;
            }
        }

        return false;
    }

    protected function isUpload(mixed $value): bool
    {
        if (is_resource($value)) {
            return true;
        }

        if ($value instanceof \SplFileInfo) {
            return true;
        }

        return is_array($value)
            && isset($value['contents'])
            && (is_resource($value['contents']) || is_string($value['contents']));
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    protected function toMultipart(array $params): array
    {
        $multipart = [];

        foreach ($params as $name => $value) {
            if ($this->isUpload($value)) {
                if (is_resource($value)) {
                    $multipart[] = [
                        'name' => (string) $name,
                        'contents' => $value,
                    ];
                    continue;
                }

                if ($value instanceof \SplFileInfo) {
                    $path = $value->getPathname();
                    $multipart[] = [
                        'name' => (string) $name,
                        'contents' => fopen($path, 'r'),
                        'filename' => $value->getFilename(),
                    ];
                    continue;
                }

                /** @var array{contents: mixed, filename?: string, headers?: array<string, string>} $value */
                $part = [
                    'name' => (string) $name,
                    'contents' => $value['contents'],
                ];
                if (isset($value['filename'])) {
                    $part['filename'] = $value['filename'];
                }
                if (isset($value['headers'])) {
                    $part['headers'] = $value['headers'];
                }
                $multipart[] = $part;
                continue;
            }

            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            } elseif (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $multipart[] = [
                'name' => (string) $name,
                'contents' => (string) $value,
            ];
        }

        return $multipart;
    }
}
