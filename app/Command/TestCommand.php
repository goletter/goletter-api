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

use App\Amqp\Queue\AccountRechargeQueue;
use Goletter\Adv\AdvFactory;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    protected AdvFactory $advFactory;

    public function __construct()
    {
        parent::__construct('test:to');
    }

    public function configure(): void
    {
        parent::configure();
        $this->setDescription('测试账户充值分片队列 / 死信');
    }

    public function handle()
    {
        $data = ['account_id' => 1, 'tenant_id' => 1, 'busine_id' => 1, 'status' => 1];
        $ok = AccountRechargeQueue::produce($data);
        $this->line($ok ? '已发送' : '发送失败');
    }
}
