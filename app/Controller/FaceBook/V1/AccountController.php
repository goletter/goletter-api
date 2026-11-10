<?php

declare(strict_types=1);

namespace App\Controller\FaceBook\V1;

use App\Controller\AbstractController;
use App\Service\AccountService;
use App\Request\FaceBook\V1\Account\IndexAccountRequest;
use App\Request\FaceBook\V1\Account\ShowAccountRequest;
use App\Request\FaceBook\V1\Account\StoreAccountRequest;
use App\Resource\FaceBook\V1\AccountResource;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc Account
  * @author yong 2026-11-09
  */
class AccountController extends AbstractController
{
    #[Inject]
    private AccountService $service;

    /**
      * @desc Account列表
      * @author yong 2026-11-09
      */
    public function index(IndexAccountRequest $request): Collection
    {
        return $this->collection(
            $this->service->paginate($request->all()),
            AccountResource::class
        );
    }

    /**
      * @desc Account详情
      * @author yong 2026-11-09
      */
    public function show(ShowAccountRequest $request): Resource
    {
        return $this->success(
            $this->service->show($request->all()),
            AccountResource::class
        );
    }

    /**
      * @desc Account添加
      * @author yong 2026-11-09
      */
    public function store(StoreAccountRequest $request): Resource
    {
        return $this->success(
            $this->service->create($request->validated()),
            AccountResource::class
        );
    }
}
