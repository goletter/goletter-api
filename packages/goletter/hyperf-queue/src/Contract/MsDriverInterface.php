<?php

declare(strict_types=1);

namespace Goletter\Queue\Contract;

use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\AsyncQueue\JobInterface;

interface MsDriverInterface extends DriverInterface
{
    /**
     * Push a job with millisecond delay.
     *
     * Same semantics as push() on RedisMsDriver: $delayMs is milliseconds.
     *
     * @param int $delayMs delay in milliseconds; 0 means immediate
     */
    public function pushMs(JobInterface $job, int $delayMs = 0): bool;

    /**
     * Delete a delayed job (works for both second and millisecond delayed payloads).
     */
    public function delete(JobInterface $job): bool;
}
