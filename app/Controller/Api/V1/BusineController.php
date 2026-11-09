<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Request\Api\V1\Busine\AccountBusineRequest;
use App\Service\BusineService;
use App\Request\Api\V1\Busine\IndexBusineRequest;
use App\Resource\Api\V1\BusineResource;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc Busine
  * @author yong 2026-11-09
  */
class BusineController extends AbstractController
{
    #[Inject]
    private BusineService $service;

    /**
      * @desc Busine列表
      * @author yong 2026-11-09
      */
    public function index(IndexBusineRequest $request): Collection
    {
        return $this->collection(
            $this->service->paginate($request->all()),
            BusineResource::class
        );
    }

    /**
      * @desc 获取已进入Bm成员列表
      * @author yong 2026-11-09
      */
    public function users(): Collection
    {
        return $this->collection( $this->service->users($this->request->all()));
    }

    /**
      * @desc 获取待进入Bm成员列表
      * @author yong 2026-11-09
      */
    public function pendingUsers(): Collection
    {
        return $this->collection( $this->service->pendingUsers($this->request->all()));
    }

    /**
      * @desc 获取Bm下系统成员列表
      * @author yong 2026-11-09
      */
    public function systemUsers(): Collection
    {
        return $this->collection( $this->service->systemUsers($this->request->all()));
    }

    /**
      * @desc 获取Bm下所有账户列表
      * @author yong 2026-11-09
      */
    public function accounts(AccountBusineRequest $request): Collection
    {
        return $this->collection( $this->service->accounts($request->all()), BusineResource::class);
    }

    /**
      * @desc 邀请成员进入Bm
      * @author yong 2026-11-09
      */
    public function invite(): Resource
    {
        return $this->success();
    }
}
