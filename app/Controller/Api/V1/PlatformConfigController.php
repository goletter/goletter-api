<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Model\PlatformConfig;
use App\Service\PlatformConfigService;
use App\Request\Api\V1\PlatformConfig\IndexPlatformConfigRequest;
use App\Request\Api\V1\PlatformConfig\ShowPlatformConfigRequest;
use App\Request\Api\V1\PlatformConfig\StorePlatformConfigRequest;
use App\Request\Api\V1\PlatformConfig\UpdatePlatformConfigRequest;
use App\Resource\Api\V1\PlatformConfigResource;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc PlatformConfig
  * @author yong 2026-11-07
  */
class PlatformConfigController extends AbstractController
{
    #[Inject]
    private PlatformConfigService $service;

    /**
      * @desc PlatformConfig列表
      * @author yong 2026-11-07
      */
    public function index(IndexPlatformConfigRequest $request): Collection
    {
        return $this->collection(
            $this->service->paginate($request->all()),
            PlatformConfigResource::class
        );
    }

    /**
      * @desc PlatformConfig详情
      * @author yong 2026-11-07
      */
    public function show(ShowPlatformConfigRequest $request): Resource
    {
        return $this->success(
            $this->service->show($request->all()),
            PlatformConfigResource::class
        );
    }

    /**
      * @desc PlatformConfig添加
      * @author yong 2026-11-07
      */
    public function store(StorePlatformConfigRequest $request): Resource
    {
        return $this->success(
            $this->service->create($request->validated()),
            PlatformConfigResource::class
        );
    }

    /**
      * @desc PlatformConfig更新
      * @author yong 2026-11-07
      */
    public function update(PlatformConfig $platformConfig, UpdatePlatformConfigRequest $request): Resource
    {
        return $this->success(
            $this->service->update($platformConfig, $request->validated()),
            PlatformConfigResource::class
        );
    }
}
