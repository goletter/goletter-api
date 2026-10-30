<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Amqp\Queue\NotifyWebhookQueue;
use App\Amqp\Sharded\ShardedConsumer;
use Goletter\Webhook\WebhookClient;
use Hyperf\Amqp\Result;
use Hyperf\Collection\Arr;
use Hyperf\Di\Annotation\Inject;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

use function Goletter\Utils\logging;

class NotifyWebhookConsumer extends ShardedConsumer
{
    #[Inject]
    private WebhookClient $webhookClient;

    protected function handle(mixed $data, AMQPMessage $message): Result
    {
        $payload = is_array($data) ? $data : [];
        $url = (string) Arr::get($payload, 'url', '');
        $event = (string) Arr::get($payload, 'event', '');
        $secret = (string) Arr::get($payload, 'secret', '');
        $bodyData = Arr::get($payload, 'payload', []);

        if ($url === '' || $event === '') {
            logging([
                'event' => 'webhook_skip_invalid',
                'shard' => $this->getShard(),
                'payload' => $payload,
            ], 'Webhook 消息缺少 url/event，丢弃', 'webhook');

            return Result::DROP;
        }

        $body = [
            'event' => $event,
            'data' => is_array($bodyData) ? $bodyData : [],
            'occurred_at' => date('c'),
        ];

        $result = $this->webhookClient->post($url, $body, $secret);
        if (! ($result['ok'] ?? false)) {
            logging([
                'event' => 'webhook_http_failed',
                'shard' => $this->getShard(),
                'url' => $url,
                'webhook_event' => $event,
                'result' => $result,
            ], 'Webhook HTTP 推送失败', 'webhook');

            throw new RuntimeException((string) ($result['message'] ?? 'webhook push failed'));
        }

        logging([
            'event' => 'webhook_http_ok',
            'shard' => $this->getShard(),
            'url' => $url,
            'webhook_event' => $event,
            'status' => $result['status'] ?? null,
        ], 'Webhook 推送成功', 'webhook');

        return Result::ACK;
    }

    /**
     * 失败重推：优先使用消息内的 retry_delay_ms（来自平台租户配置）。
     */
    protected function retryOrDrop(array $payload, Throwable $e): Result
    {
        $queueClass = static::queueClass();
        $attempts = (int) ($payload['attempts'] ?? 0) + 1;
        $maxAttempts = max(1, $queueClass::maxAttempts());

        logging([
            'event' => 'retry',
            'exchange' => $queueClass::exchange(),
            'shard' => $this->getShard(),
            'key' => $queueClass::serialKey($payload),
            'attempts' => $attempts,
            'max_attempts' => $maxAttempts,
            'retry_delay_ms' => (int) ($payload['retry_delay_ms'] ?? $queueClass::retryDelayMs()),
            'exception' => $e::class,
            'error' => $e->getMessage(),
            ...$payload,
        ], 'Webhook 分片队列重试', 'webhook');

        if ($attempts >= $maxAttempts) {
            logging([
                'event' => 'retry_exhausted',
                'exchange' => $queueClass::exchange(),
                'dlq' => $queueClass::dlq(),
                'shard' => $this->getShard(),
                'key' => $queueClass::serialKey($payload),
                'attempts' => $attempts,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ], 'Webhook 重试耗尽，将进入死信', 'webhook');

            return Result::DROP;
        }

        $delayMs = max(0, (int) ($payload['retry_delay_ms'] ?? $queueClass::retryDelayMs()));
        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }

        $payload['attempts'] = $attempts;
        $queueClass::produce($payload);

        return Result::ACK;
    }

    protected static function queueClass(): string
    {
        return NotifyWebhookQueue::class;
    }
}
