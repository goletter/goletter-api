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
use App\Model\AccountDemand;
use App\Model\DocsPlatformConfig;
use App\Service\Adv\AdvResolver;
use Goletter\Docs\DocsManager;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\logging;

#[Command]
class TestCommand extends HyperfCommand
{
    #[Inject]
    protected DocsManager $docs;

    #[Inject]
    private AdvResolver $advResolver;

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
        $docsPlatformConfig = DocsPlatformConfig::query()->first();
        $accountDemands = AccountDemand::query()->where('status', 0)->get();

        foreach ($accountDemands as $index => $accountDemand) {
            $data = [
                'account_demand_id' => $accountDemand->id,
                'tenant_id' => $accountDemand->tenant_id,
                'busine_id' => $accountDemand->busine_code,
                'access_token' => $docsPlatformConfig->token,
            ];

            $ok = AccountRechargeQueue::produce($data);
            $nameLine = $ok ? '已发送' : '发送失败';

            $this->line("{$index}========{$accountDemand->money}========={$nameLine}");
        }
    }
}
