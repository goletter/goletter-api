<?php

declare(strict_types=1);

namespace Goletter\Akm;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
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
                    'description' => '蓝瀚 AKM OpenAPI 配置。',
                    'source' => __DIR__ . '/../publish/akm.php',
                    'destination' => BASE_PATH . '/config/autoload/akm.php',
                ],
            ],
        ];
    }
}
