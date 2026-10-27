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
use App\Amqp\Producer\AccountRechargeProducer;
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
        $messages = [
            ['account_id' => 1, 'tenant_id' => 1, 'busine_id' => 1, 'status' => 1],
            ['account_id' => 2, 'tenant_id' => 1, 'busine_id' => 1, 'status' => 2],
            ['account_id' => 3, 'tenant_id' => 2, 'busine_id' => 3, 'status' => 3],
            ['account_id' => 4, 'tenant_id' => 2, 'busine_id' => 3, 'status' => 4],
            // status=998：抛错重试至耗尽后进死信
            ['account_id' => 5, 'tenant_id' => 1, 'busine_id' => 1, 'status' => 998],
            // status=999：主动 DROP 进死信
            ['account_id' => 6, 'tenant_id' => 1, 'busine_id' => 1, 'status' => 999],
        ];

        foreach ($messages as $data) {
            $shard = AccountRechargeQueue::shard($data);
            $ok = produce(new AccountRechargeProducer($data));

            $this->line(sprintf(
                'account %d -> %s (%s) status=%s %s',
                $data['account_id'],
                AccountRechargeQueue::queue($shard),
                AccountRechargeQueue::serialKey($data),
                $data['status'],
                $ok ? '已发送' : '发送失败'
            ));
        }
    }
}
