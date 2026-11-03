#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * 不经过 Hyperf 命令容器的兜底生成脚本（便于排查 openapi:generate 为空）.
 *
 *   php bin/openapi-generate.php
 *   php bin/openapi-generate.php v1
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use Goletter\OpenApi\Generator\OpenApiGenerator;
use Goletter\OpenApi\Scanner\DispatcherControllerScanner;
use Hyperf\Contract\ConfigInterface;

$configFile = BASE_PATH . '/config/autoload/openapi.php';
if (! is_file($configFile)) {
    fwrite(STDERR, "缺少配置：{$configFile}\n");
    exit(1);
}

/** @var array<string, mixed> $openapi */
$openapi = require $configFile;

$config = new class($openapi) implements ConfigInterface {
    public function __construct(private array $openapi)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($key === 'openapi') {
            return $this->openapi;
        }
        if (str_starts_with($key, 'openapi.')) {
            $path = explode('.', substr($key, strlen('openapi.')));
            $value = $this->openapi;
            foreach ($path as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    return $default;
                }
                $value = $value[$segment];
            }

            return $value;
        }

        return $default;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function set(string $key, mixed $value): void
    {
        // no-op for script
    }
};

$version = $argv[1] ?? null;
$generator = new OpenApiGenerator($config);

echo "BASE_PATH=" . BASE_PATH . PHP_EOL;
echo "versions=" . implode(',', $generator->discoverVersions()) . PHP_EOL;

try {
    $written = $generator->write($version);
} catch (Throwable $e) {
    fwrite(STDERR, 'ERROR: ' . $e->getMessage() . PHP_EOL);
    foreach ($generator->getScanDebug() as $line) {
        echo '  · ' . $line . PHP_EOL;
    }
    exit(1);
}

foreach ($generator->getScanDebug() as $line) {
    echo '  · ' . $line . PHP_EOL;
}

foreach ($written as $row) {
    echo sprintf("[%s] paths=%d\n", $row['version'], $row['paths']);
    if ($row['json']) {
        echo '  JSON: ' . $row['json'] . PHP_EOL;
    }
    if ($row['yaml']) {
        echo '  YAML: ' . $row['yaml'] . PHP_EOL;
    }
}
