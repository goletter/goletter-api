<?php
declare(strict_types=1);

namespace App\Controller\Admin\V2;

use App\Controller\AbstractController;
use App\Model\Account;
use App\Service\AccountService;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc Account
  * @author yong 2026-11-06
  */
class AccountController extends AbstractController
{
    #[Inject]
    private AccountService $service;

    /**
      * @desc Account列表
      * @author yong 2026-11-06
      */
    public function index()
    {
        return $this->collection($this->service->paginate($this->request->all()));
    }

    /**
      * @desc Account详情
      * @author yong 2026-11-06
      */
    public function show(Account $account)
    {
        return $this->success($account);
    }

    /**
      * @desc Account添加
      * @author yong 2026-11-06
      */
    public function store()
    {
        return $this->success($this->service->create($this->request->all()));
    }

    /**
      * @desc Account更新
      * @author yong 2026-11-06
      */
    public function update(Account $account)
    {
        return $this->success($this->service->update($account, $this->request->all()));
    }

    /**
      * @desc Account删除
      * @author yong 2026-11-06
      */
    public function destroy(Account $account)
    {
        $this->service->delete($account);

        return $this->success();
    }
}
