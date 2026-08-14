<?php

declare(strict_types=1);

namespace Goletter\Queue;

use Goletter\Queue\Contract\MsDriverInterface;
use Goletter\Queue\Exception\InvalidDriverException;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\AsyncQueue\JobInterface;
use Hyperf\Context\ApplicationContext;

/**
 * Convenience API for millisecond / second delay pushes.
 */
final class Queue
{
    public const POOL_DEFAULT = 'default';

    public const POOL_MS = 'ms';

    public static function push(JobInterface $job, ?string $pool = null): bool
    {
        return self::driver($pool ?? self::POOL_DEFAULT)->push($job, 0);
    }

    /**
     * Delay in seconds on the given pool (default: official second-based pool).
     */
    public static function later(int $delaySeconds, JobInterface $job, ?string $pool = null): bool
    {
        return self::driver($pool ?? self::POOL_DEFAULT)->push($job, max(0, $delaySeconds));
    }

    /**
     * Delay in milliseconds (default pool: ms).
     */
    public static function laterMs(int $delayMs, JobInterface $job, ?string $pool = null): bool
    {
        $driver = self::driver($pool ?? self::POOL_MS);
        if ($driver instanceof MsDriverInterface) {
            return $driver->pushMs($job, max(0, $delayMs));
        }

        throw new InvalidDriverException(sprintf(
            'Pool driver must implement %s to support millisecond delay, got %s.',
            MsDriverInterface::class,
            $driver::class
        ));
    }

    /**
     * Execute at an absolute unix timestamp in milliseconds (default pool: ms).
     */
    public static function atMs(int $executeAtMs, JobInterface $job, ?string $pool = null): bool
    {
        $delayMs = $executeAtMs - (int) floor(microtime(true) * 1000);

        return self::laterMs(max(0, $delayMs), $job, $pool);
    }

    public static function delete(JobInterface $job, ?string $pool = null): bool
    {
        return self::driver($pool ?? self::POOL_DEFAULT)->delete($job);
    }

    public static function deleteMs(JobInterface $job, ?string $pool = null): bool
    {
        return self::driver($pool ?? self::POOL_MS)->delete($job);
    }

    public static function driver(?string $pool = null): DriverInterface
    {
        return ApplicationContext::getContainer()
            ->get(DriverFactory::class)
            ->get($pool ?? self::POOL_DEFAULT);
    }
}
