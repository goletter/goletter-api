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

use App\Logic\AccountDemand\AccountDemandTrait;
use Goletter\Server\BaseService;

/**
  * @desc 需求列表
  * @author yong 2026-11-07
  */
class AccountDemandService extends BaseService
{
    use AccountDemandTrait;

    /**
      * @desc 列表
      * @author yong 2026-11-07
      */
    public function paginate(array $params)
    {
        return $this->indexLogic->handle($params);
    }

    /**
      * @desc 充值需求添加
      * @author yong 2026-11-07
      */
    public function create(array $data)
    {
        $accountDemand = $this->createLogic->handle($data);

        return ['order_no' => $accountDemand->order_no];
    }
}
