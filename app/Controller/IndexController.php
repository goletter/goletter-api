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

namespace App\Controller;

use App\Event\TestEvent;
use App\Job\TestJob;
use App\Service\TestService;
use Goletter\Server\Service\QueueService;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\event;

class IndexController extends AbstractController
{
    #[Inject]
    private TestService $service;

    #[Inject]
    private QueueService $queueService;

    public function index()
    {
        logging([], '111', 'test');

        $this->service->index();

        event()->dispatch(new TestEvent());

        $this->queueService->push(new TestJob());

        return $this->success();
    }
}
