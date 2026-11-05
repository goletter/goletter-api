<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Service\AccountService;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc 账户管理
  * @author yong 2026-11-03
  */
class AccountController extends AbstractController
{
    #[Inject]
    private AccountService $service;

    /**
      * @desc 账户列表
      * @author yong 2026-11-03
      */
    public function index()
    {
        $data = ['status' => 123456];
        return $this->success($data);
    }

    /**
      * @desc 账户详情
      * @author yong 2026-11-03
      */
    public function detail()
    {
        return $this->success($this->service->detail($this->request->all()));
    }
}