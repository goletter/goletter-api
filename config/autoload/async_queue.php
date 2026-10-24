<?php

declare(strict_types=1);

return [
    'default' => [
        'driver' => Hyperf\AsyncQueue\Driver\RedisDriver::class,
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => 'queue',
        'timeout' => 10,
        'retry_seconds' => [10, 30, 60],
        'handle_timeout' => 10,
        'processes' => 1,
        'concurrent' => [
            'limit' => 1,
        ],
        'max_messages' => 0,
    ],
    // Millisecond-precision pool. Keep channel distinct from second-based pools.
    'ms' => [
        'driver' => Goletter\Queue\Driver\RedisMsDriver::class,
        'redis' => [
            'pool' => 'default',
        ],
        'channel' => 'queue-ms',
        'timeout' => 2,
        'retry_seconds' => [10, 30, 60],
        'handle_timeout' => 10,
        'processes' => 1,
        'concurrent' => [
            'limit' => 1,
        ],
        'max_messages' => 0,
    ],
];
