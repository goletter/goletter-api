<?php

declare(strict_types=1);

namespace App\Callback;

use App\Callback\Contract\CallbackHandlerInterface;
use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

final class CallbackDispatcher
{
    public function __construct(
        private ConfigInterface $config,
        private ContainerInterface $container,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function dispatch(string $event, array $payload): mixed
    {
        $class = $this->config->get('callbacks.handlers.' . $event);
        if (! is_string($class) || $class === '') {
            throw new RuntimeException('未注册的回调事件: ' . $event);
        }

        $handler = $this->container->get($class);
        if (! $handler instanceof CallbackHandlerInterface) {
            throw new RuntimeException($class . ' 必须实现 CallbackHandlerInterface');
        }

        return $handler->handle($event, $payload);
    }
}
