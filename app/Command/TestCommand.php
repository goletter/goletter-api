<?php

declare(strict_types=1);

namespace App\Command;

use App\Amqp\Queue\AccountRechargeQueue;
use App\Model\AccountDemand;
use App\Model\PlatformConfig;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;

#[Command]
class TestCommand extends HyperfCommand
{
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
        $data = [
            'code' => '27655814800671086',
            'name' => 'QQ Plus MX-TOY-1026+8-014',
            'type' => 1,
            'busine_code' => '134179179769131',
            'sheel_url' => 'https://docs.google.com/spreadsheets/d/1DD3Wrr1hHWTm5QWMvgWg8LiM3Wpd-NMw-gaEpiJFrp0/edit?gid=1145273265#gid=1145273265',
        ];
        for ($i=0; $i < 10; $i++) {
            $data['tenant_id'] = 1;
            $data['order_no'] = setOrderNo(AccountDemand::class);
            $data['money'] = mt_rand(100, 999);

            $accountDemand = new AccountDemand();
            $accountDemand->fillDb($data);
            $accountDemand->save();

            $platformConfig = PlatformConfig::query()->first();
            AccountRechargeQueue::produce([
                'account_demand_id' => $accountDemand->id,
                'tenant_id' => 1,
                'busine_id' => $accountDemand->busine_code,
                'access_token' => $platformConfig->token,
            ]);
        }
    }
}
