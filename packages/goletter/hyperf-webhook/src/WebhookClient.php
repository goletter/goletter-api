<?php

declare(strict_types=1);

namespace Goletter\Webhook;

use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\ClientFactory;
use Throwable;

/**
 * 出站 Webhook HTTP 客户端：POST JSON，可选 HMAC 签名。
 */
class WebhookClient
{
    public function __construct(
        protected ClientFactory $clientFactory,
        protected ConfigInterface $config,
    ) {
    }

    /**
     * @param array<string, mixed> $payload 完整请求体（含 event / data 等）
     * @return array{ok: bool, status?: int, message?: string, body?: string}
     */
    public function post(string $url, array $payload, string $secret = ''): array
    {
        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            return ['ok' => false, 'message' => 'Failed to encode webhook payload'];
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($secret !== '') {
            $headerName = (string) $this->config->get('webhook.signature_header', 'X-Goletter-Signature');
            $headers[$headerName !== '' ? $headerName : 'X-Goletter-Signature'] = Signature::sign($body, $secret);
        }

        $client = $this->clientFactory->create([
            'timeout' => (float) $this->config->get('webhook.timeout', 5.0),
            'connect_timeout' => (float) $this->config->get('webhook.connect_timeout', 2.0),
            'http_errors' => false,
        ]);

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'body' => $body,
            ]);
        } catch (GuzzleException|Throwable $e) {
            return ['ok' => false, 'message' => 'Webhook unreachable: ' . $e->getMessage()];
        }

        $status = $response->getStatusCode();
        $raw = (string) $response->getBody();

        if ($status < 200 || $status >= 300) {
            return [
                'ok' => false,
                'status' => $status,
                'message' => 'Webhook HTTP ' . $status,
                'body' => mb_substr($raw, 0, 500),
            ];
        }

        return ['ok' => true, 'status' => $status, 'body' => mb_substr($raw, 0, 500)];
    }
}
