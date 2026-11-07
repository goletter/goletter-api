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

namespace App\Logic\Account;

use App\Model\Account;
use Goletter\Server\BaseService;

class UpdateLogic extends BaseService
{
    public function handle(Account $account, array $data)
    {
        $account->fillDb($data);
        $account->save();

        return $account;
    }
}