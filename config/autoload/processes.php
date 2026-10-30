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
return [
    Hyperf\Crontab\Process\CrontabDispatcherProcess::class,
    // 分片 AMQP 消费（不依赖 #[Process] 扫描，避免 runtime 缓存漏扫导致不消费）
    App\Process\AccountShardedConsumeProcess::class,
];
