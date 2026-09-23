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

namespace App\Command;

use App\Job\TestJob;
use Goletter\Server\Service\QueueService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\di;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    private QueueService $queueService;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试');
    }

    public function handle()
    {
        for ($i=0; $i<100; $i++) {
            $key = sprintf('tenant:%d:biz:%d', $i, $i);
            $this->queueService->pushSerial($key, new TestJob($i), 'default', 1);
        }
    }
}
