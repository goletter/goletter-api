<?php

declare(strict_types=1);

namespace App\Controller\Api\V2;

use App\Controller\Api\V1\AccountDemandController as V1AccountDemandController;

/**
  * @desc 需求管理
  * @author yong 2026-11-03
  */
class AccountDemandController extends V1AccountDemandController
{
    /**
      * @desc 需求详情
      * @author yong 2026-11-03
      */
    public function detail()
    {
        return $this->success();
    }
}
