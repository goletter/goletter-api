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

use App\Model\AccountDemand;
use Goletter\Server\BaseService;

/**
  * @desc 更新
  * @author yong 2026-11-07
  */
class UpdateLogic extends BaseService
{
    public function handle(AccountDemand $accountDemand, array $data)
    {
        return $accountDemand;
    }
}
