<?php

declare(strict_types=1);

namespace Goletter\Mtls;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
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
                    'description' => 'The config of mTLS certificate generator.',
                    'source' => __DIR__ . '/../publish/mtls.php',
                    'destination' => BASE_PATH . '/config/autoload/mtls.php',
                ],
                [
                    'id' => 'migration',
                    'description' => 'Create client certificates table.',
                    'source' => __DIR__ . '/../publish/migrations/2026_07_25_000000_create_client_certificates_table.php',
                    'destination' => BASE_PATH . '/migrations/2026_07_25_000000_create_client_certificates_table.php',
                ],
            ],
        ];
    }
}
