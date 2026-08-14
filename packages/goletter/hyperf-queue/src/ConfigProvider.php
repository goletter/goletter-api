<?php

declare(strict_types=1);

namespace Goletter\Queue;

use Goletter\Queue\Command\InfoCommand;
use Goletter\Queue\Process\MsQueueConsumer;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'commands' => [
                InfoCommand::class,
            ],
            'processes' => [
                MsQueueConsumer::class,
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config for goletter millisecond async queue.',
                    'source' => __DIR__ . '/../publish/async_queue.php',
                    'destination' => BASE_PATH . '/config/autoload/async_queue.php',
                ],
            ],
        ];
    }
}
