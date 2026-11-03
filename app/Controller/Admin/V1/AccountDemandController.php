<?php

declare(strict_types=1);

namespace App\Controller\Admin\V1;

use App\Controller\AbstractController;

/**
  * @desc 需求管理
  * @author yong 2026-11-03
  */
class AccountDemandController extends AbstractController
{
    /**
      * @desc 需求列表
      * @author yong 2026-11-03
      */
    public function index()
    {
        return $this->success();
    }
}
