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

use App\Amqp\Queue\AccountBindQueue;
use App\Service\Adv\AdvResolver;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\logging;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    private AdvResolver $advResolver;

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
        AccountBindQueue::produce([
            'account_demand_id' => 1,
            'tenant_id' => 1,
            'busine_id' => 1,
            'access_token' => 1,
        ]);
    }
}
