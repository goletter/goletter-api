<?php

declare(strict_types=1);

namespace App\Notifier\Handler;

use App\Model\Model;
use App\Notifier\Contract\OutboundHandlerInterface;
use App\Service\Webhook\WebhookNotifier;
use InvalidArgumentException;

/**
 * 通用：context 为 Model 时走 WebhookNotifier::notify。
 * 特殊载荷可另建 Handler，在 notifiers.php 单独注册。
 */
final class ModelWebhookHandler implements OutboundHandlerInterface
{
    public function __construct(
        private WebhookNotifier $webhookNotifier,
    ) {
    }

    public function handle(string $event, mixed $context): bool
    {
        if (! $context instanceof Model) {
            throw new InvalidArgumentException('ModelWebhookHandler 需要 App\\Model\\Model 上下文');
        }

        return $this->webhookNotifier->notify($context, $event);
    }
}
