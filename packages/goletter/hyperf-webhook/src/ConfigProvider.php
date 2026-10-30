<?php

declare(strict_types=1);

namespace Goletter\Webhook;

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
                    'description' => 'Webhook 出站推送 / HMAC 签名配置。',
                    'source' => __DIR__ . '/../publish/webhook.php',
                    'destination' => BASE_PATH . '/config/autoload/webhook.php',
                ],
            ],
        ];
    }
}
