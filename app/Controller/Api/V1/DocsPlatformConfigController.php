<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Model\DocsPlatformConfig;
use App\Request\Api\V1\DocsPlatformConfigRequest;
use App\Resource\Api\V1\DocsPlatformConfigResource;
use App\Service\DocsPlatformConfigService;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

class DocsPlatformConfigController extends AbstractController
{
    #[Inject]
    private DocsPlatformConfigService $service;

    public function index(): Collection
    {
        return $this->collection(
            $this->service->paginate($this->request->all()),
            DocsPlatformConfigResource::class
        );
    }

    public function store(DocsPlatformConfigRequest $request): Resource
    {
        return $this->success(
            $this->service->create($request->validated()),
            DocsPlatformConfigResource::class
        );
    }

    public function update(DocsPlatformConfig $docsPlatformConfig, DocsPlatformConfigRequest $request): Resource
    {
        return $this->success(
            $this->service->update($docsPlatformConfig, $request->validated()),
            DocsPlatformConfigResource::class
        );
    }
}
