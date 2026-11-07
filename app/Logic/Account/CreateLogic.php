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

class CreateLogic extends BaseService
{
    public function handle(array $data)
    {
        $account = new Account();
        $account->fillDb($data);
        $account->save();

        return $account;
    }
}
