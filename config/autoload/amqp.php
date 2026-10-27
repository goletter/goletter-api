<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Hyperf\Amqp\IO\IOFactory;

use function Hyperf\Support\env;

return [
    'enable' => true,
    'default' => [
        'host' => env('AMQP_HOST', 'localhost'),
        'port' => (int) env('AMQP_PORT', 5672),
        'user' => env('AMQP_USER', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest'),
        'vhost' => env('AMQP_VHOST', '/'),
        'open_ssl' => false,
        // 应 ≥ qos.prefetch_count；分片消费协程多，适当放大
        'concurrent' => [
            'limit' => 16,
        ],
        // 5 队列 × 16 分片 ≈ 80 消费协程，连接池过小易握手超时
        'pool' => [
            'connections' => (int) env('AMQP_POOL_CONNECTIONS', 32),
        ],
        'io' => IOFactory::class,
        'params' => [
            'insist' => false,
            'login_method' => 'AMQPLAIN',
            'login_response' => null,
            'locale' => 'en_US',
            // 远程 Broker 建议 ≥10
            'connection_timeout' => (float) env('AMQP_CONNECTION_TIMEOUT', 10),
            // 尽量保持约为 heartbeat 的 2 倍
            'read_write_timeout' => (float) env('AMQP_READ_WRITE_TIMEOUT', 60),
            'context' => null,
            'keepalive' => true,
            // 单条处理（含 retryDelay）应尽量小于 heartbeat
            'heartbeat' => (int) env('AMQP_HEARTBEAT', 30),
            'channel_rpc_timeout' => 0.0,
            'close_on_destruct' => false,
            'max_idle_channels' => 20,
            'connection_name' => null,
        ],
    ],
];
