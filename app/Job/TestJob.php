<?php

declare(strict_types=1);

namespace App\Job;

use Hyperf\AsyncQueue\Job;

class TestJob extends Job
{
    protected int $maxAttempts = 1;

    public function handle()
    {
        logging([], '444', 'test');
    }
}
