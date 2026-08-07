<?php

declare(strict_types=1);

namespace Goletter\Telegram;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                // 动态多 Bot 请注入 BotFactory。
                // 若仍需默认 BotInterface，请自行在项目 dependencies 中绑定：
                // BotInterface::class => fn ($c) => $c->get(BotFactory::class)->get(),
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
                    'description' => 'The config of Telegram Bot client.',
                    'source' => __DIR__ . '/../publish/telegram.php',
                    'destination' => BASE_PATH . '/config/autoload/telegram.php',
                ],
            ],
        ];
    }
}
