<?php

declare(strict_types=1);

namespace App\Notifier;

use App\Notifier\Contract\OutboundHandlerInterface;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

final class OutboundDispatcher
{
    public function __construct(
        private ConfigInterface $config,
        private ContainerInterface $container,
    ) {
    }

    /**
     * 按事件名分发出站通知（与入站 CallbackDispatcher 对称）。
     */
    public function emit(string $event, mixed $context): bool
    {
        $class = $this->config->get('notifiers.handlers.' . $event);
        if (! is_string($class) || $class === '') {
            throw new RuntimeException('未注册的出站事件: ' . $event);
        }

        $handler = $this->container->get($class);
        if (! $handler instanceof OutboundHandlerInterface) {
            throw new RuntimeException($class . ' 必须实现 OutboundHandlerInterface');
        }

        return $handler->handle($event, $context);
    }
}
