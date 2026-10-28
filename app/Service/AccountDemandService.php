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

use App\Model\AccountDemand;
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
        return $this->save(new AccountDemand(), $data);
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
