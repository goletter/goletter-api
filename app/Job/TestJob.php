<?php

declare(strict_types=1);

namespace App\Job;

use Carbon\Carbon;
use Hyperf\AsyncQueue\Job;

use function Goletter\Utils\logging;

class TestJob extends Job
{
    public function __construct(protected int $id){}

    public function handle()
    {
        logging(['id' => $this->id, 'time' => Carbon::now()->toDateTimeString()], 'TestJob', 'test');
    }
}
