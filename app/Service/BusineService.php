<?php

declare(strict_types=1);

namespace App\Service;

use App\Logic\Busine\BusineTrait;
use Goletter\Server\BaseService;

/**
  * @desc Busine
  * @author yong 2026-11-09
  */
class BusineService extends BaseService
{
    use BusineTrait;

    /**
      * @desc 成员列表
      * @author yong 2026-11-09
      */
    public function users(array $data)
    {
        return $this->userLogic->handle($data);
    }

    /**
      * @desc 待邀请成员
      * @author yong 2026-11-09
      */
    public function pendingUsers(array $data)
    {
        return $this->pendingUserLogic->handle($data);
    }

    /**
      * @desc 系统用户列表
      * @author yong 2026-11-09
      */
    public function systemUsers(array $data)
    {
        return $this->systemUserLogic->handle($data);
    }

    /**
      * @desc Bm账户列表
      * @author yong 2026-11-09
      */
    public function accounts(array $data)
    {
        return $this->accountLogic->handle($data);
    }
}
