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

class PlatformConfigController extends AbstractController
{
    #[Inject]
    private PlatformConfigService $service;

    public function index(): Collection
    {
        return $this->collection(
            $this->service->paginate($this->request->all()),
            PlatformConfigResource::class
        );
    }

    public function store(PlatformConfigResource $request): Resource
    {
        return $this->success(
            $this->service->create($request->validated()),
            PlatformConfigResource::class
        );
    }

    public function update(PlatformConfig $platformConfig, PlatformConfigRequest $request): Resource
    {
        return $this->success(
            $this->service->update($platformConfig, $request->validated()),
            PlatformConfigResource::class
        );
    }
}
