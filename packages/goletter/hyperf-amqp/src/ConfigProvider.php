<?php

declare(strict_types=1);

namespace Goletter\Amqp;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
            ],
            // 命令由应用 config/autoload/commands.php 注册
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => '分片 AMQP 队列生成路径等配置。',
                    'source' => __DIR__ . '/../publish/amqp_sharded.php',
                    'destination' => BASE_PATH . '/config/autoload/amqp_sharded.php',
                ],
            ],
        ];
    }
}
