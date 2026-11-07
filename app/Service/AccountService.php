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

use App\Logic\Account\AccountTrait;
use App\Model\Account;
use Goletter\Server\BaseService;

class AccountService extends BaseService
{
    use AccountTrait;

    /**
     *  列表
     */
    public function paginate(array $params)
    {
        return $this->indexLogic->handle($params);
    }

    /**
     * 创建
     * @return Account
     */
    public function create(array $data)
    {
        return $this->createLogic->handle($data);
    }

    /**
     * 更新
     * @return Account
     */
    public function update(Account $account, array $data)
    {
        return $this->updateLogic->handle($account, $data);
    }
}
