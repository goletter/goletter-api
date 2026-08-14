<?php

declare(strict_types=1);

namespace App\Job;

use Hyperf\AsyncQueue\Job;
use function Goletter\Utils\logging;

class TestJob extends Job
{
    protected int $maxAttempts = 1;

    public function handle()
    {
        logging([], 'TestJob ms-queue check', 'test');
    }
}
