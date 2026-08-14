<?php

declare(strict_types=1);

namespace Goletter\Queue\Process;

use Hyperf\AsyncQueue\Process\ConsumerProcess;

/**
 * Consumer for the millisecond queue pool (`ms`).
 * Registered via ConfigProvider processes (not app annotation scan).
 */
class MsQueueConsumer extends ConsumerProcess
{
    protected string $pool = 'ms';
}
