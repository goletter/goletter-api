<?php

declare(strict_types=1);

namespace App\Controller;

use Goletter\Server\Service\QueueService;
use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\Di\Annotation\Inject;
use function Hyperf\Config\config;
use function Goletter\Utils\di;

class QueueController extends AbstractController
{
    #[Inject]
    private QueueService $queueService;

    public function index()
    {
        $data = [];
        $pools = config('async_queue', []);

        foreach ($pools as $pool) {
            $status = $this->queueService->getAsyncQueueCompleted($pool);
            $data[] = ['pool' => $pool, ...$status];
        }

        return $this->success($data);
    }

    public function reload()
    {
        $pool = (string) $this->request->input('pool', 'default');
        $channel = $this->request->input('channel'); // null=failed，或 timeout

        $driver = di(DriverFactory::class)->get($pool);
        $num = $driver->reload($channel);

        return $this->success(['reloaded' => $num, 'pool' => $pool]);
    }
}
