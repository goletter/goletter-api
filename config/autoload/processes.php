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

use function Hyperf\Support\env;

$processes = [];

if (filter_var(env('CRONTAB_ENABLE', true), FILTER_VALIDATE_BOOLEAN)) {
    $processes[] = Hyperf\Crontab\Process\CrontabDispatcherProcess::class;
}

if (filter_var(env('AMQP_CONSUMER_ENABLE', true), FILTER_VALIDATE_BOOLEAN)) {
    $processes[] = Goletter\Amqp\Process\ShardedQueueConsumeProcess::class;
}

return $processes;
