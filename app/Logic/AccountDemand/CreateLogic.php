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

namespace App\Logic\AccountDemand;

use App\Amqp\Queue\AccountRechargeQueue;
use App\Model\AccountDemand;
use App\Model\PlatformConfig;
use Goletter\Server\BaseService;

/**
  * @desc 创建
  * @author yong 2026-11-07
  */
class CreateLogic extends BaseService
{
    public function handle(array $data)
    {
        $data['tenant_id'] = 654123;
        $data['order_no'] = setOrderNo(AccountDemand::class);

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

        return $accountDemand;
    }
}
