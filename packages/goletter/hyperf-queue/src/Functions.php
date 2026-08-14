<?php

declare(strict_types=1);

namespace Goletter\Queue;

use Hyperf\AsyncQueue\JobInterface;

use function Hyperf\AsyncQueue\dispatch;

/**
 * @param null|int $delaySeconds delay in seconds (0 = immediate)
 */
function dispatch_job(JobInterface $job, ?int $delaySeconds = null, ?int $maxAttempts = null, ?string $pool = null): bool
{
    return dispatch($job, $delaySeconds, $maxAttempts, $pool);
}

/**
 * Dispatch with millisecond delay (default pool: ms).
 */
function dispatch_ms(JobInterface $job, int $delayMs = 0, ?int $maxAttempts = null, ?string $pool = null): bool
{
    if (is_int($maxAttempts)) {
        $job->setMaxAttempts($maxAttempts);
    }

    return Queue::laterMs($delayMs, $job, $pool ?? Queue::POOL_MS);
}
