<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Model\PlatformConfig;
use App\Request\Api\V1\PlatformConfigRequest;
use App\Resource\Api\V1\PlatformConfigResource;
use App\Service\PlatformConfigService;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc 平台配置
  * @author yong 2026-11-03
  */
class PlatformConfigController extends AbstractController
{
    #[Inject]
    private PlatformConfigService $service;

    /**
      * @desc 配置列表
      * @author yong 2026-11-03
      */
    public function index(): Collection
    {
        return $this->collection(
            $this->service->paginate($this->request->all()),
            PlatformConfigResource::class
        );
    }

    /**
      * @desc 配置新增
      * @author yong 2026-11-03
      */
    public function store(PlatformConfigResource $request): Resource
    {
        return $this->success(
            $this->service->create($request->validated()),
            PlatformConfigResource::class
        );
    }

    /**
      * @desc 配置更新
      * @author yong 2026-11-03
      */
    public function update(PlatformConfig $platformConfig, PlatformConfigRequest $request): Resource
    {
        return $this->success(
            $this->service->update($platformConfig, $request->validated()),
            PlatformConfigResource::class
        );
    }
}
