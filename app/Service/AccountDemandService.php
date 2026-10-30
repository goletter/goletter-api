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

namespace App\Service;

use App\Amqp\Queue\AccountRechargeQueue;
use App\Model\AccountDemand;
use App\Model\DocsPlatformConfig;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Paginator\LengthAwarePaginator;

class AccountDemandService extends BaseService
{
    public function paginate(array $params): LengthAwarePaginator
    {
        return AccountDemand::query()->filter($params)->paginate((int) Arr::get($params, 'pageSize', 20));
    }

    public function create(array $data)
    {
        $data['tenant_id'] = uniqid();
        $data['order_no'] = setOrderNo(AccountDemand::class);

        $accountDemand = $this->save(new AccountDemand(), $data);

        $docsPlatformConfig = DocsPlatformConfig::query()->first();
        AccountRechargeQueue::produce([
            'account_demand_id' => $accountDemand->id,
            'tenant_id' => $accountDemand->tenant_id,
            'busine_id' => $accountDemand->busine_code,
            'access_token' => $docsPlatformConfig->token,
        ]);

        return $accountDemand;
    }

    public function update(AccountDemand $accountDemand, array $data)
    {
        return $this->save($accountDemand, $data);
    }

    public function save(AccountDemand $accountDemand, array $data)
    {
        $accountDemand->fillDb($data);
        $accountDemand->save();

        return $accountDemand;
    }
}
