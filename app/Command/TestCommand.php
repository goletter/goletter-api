<?php

declare(strict_types=1);

namespace App\Command;

use App\Job\TestJob;
use Goletter\Server\Service\QueueService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    private QueueService $queueService;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('test:to');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('测试');
    }

    public function handle()
    {
        $this->queueService->push(new TestJob());
    }
}
