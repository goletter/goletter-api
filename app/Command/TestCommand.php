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

use App\Amqp\AccountDemandQueue;
use App\Amqp\Producer\AccountDemandProducer;
use Goletter\Adv\AdvFactory;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\logging;

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
        $this->setDescription('测试');
    }

    public function handle()
    {
        $messages = [
            ['account_id' => 1, 'tenant_id' => 1, 'busine_id' => 1, 'status' => 1],
            ['account_id' => 2, 'tenant_id' => 1, 'busine_id' => 1, 'status' => 2],
            ['account_id' => 3, 'tenant_id' => 2, 'busine_id' => 3, 'status' => 3],
            ['account_id' => 4, 'tenant_id' => 2, 'busine_id' => 3, 'status' => 4],
        ];
        foreach ($messages as $data) {
            $shard = AccountDemandQueue::shard($data);
            $ok = produce(new AccountDemandProducer($data));
            $this->line(sprintf(
                'account %d -> %s (%s) %s',
                $data['account_id'],
                AccountDemandQueue::queue($shard),
                AccountDemandQueue::serialKey($data),
                $ok ? '已发送' : '发送失败'
            ));
        }
    }
}
