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

use App\Model\Account;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Paginator\LengthAwarePaginator;

class AccountService extends BaseService
{
    public function paginate(array $params): LengthAwarePaginator
    {
        return Account::query()->filter($params)->paginate((int) Arr::get($params, 'pageSize', 20));
    }

    public function create(array $data)
    {
        return $this->save(new Account(), $data);
    }

    public function update(Account $account, array $data)
    {
        return $this->save($account, $data);
    }

    public function save(Account $account, array $data)
    {
        $account->fillDb($data);
        $account->save();

        return $account;
    }
}
