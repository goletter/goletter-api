<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Model\Catalog;
use App\Service\CatalogService;
use App\Request\Api\V1\Catalog\IndexCatalogRequest;
use App\Request\Api\V1\Catalog\ShowCatalogRequest;
use App\Request\Api\V1\Catalog\StoreCatalogRequest;
use App\Request\Api\V1\Catalog\UpdateCatalogRequest;
use App\Resource\Api\V1\CatalogResource;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc Catalog
  * @author yong 2026-11-09
  */
class CatalogController extends AbstractController
{
    #[Inject]
    private CatalogService $service;

    /**
      * @desc Catalog列表
      * @author yong 2026-11-09
      */
    public function index(IndexCatalogRequest $request): Collection
    {
        return $this->collection(
            $this->service->paginate($request->all()),
            CatalogResource::class
        );
    }

    /**
      * @desc Catalog详情
      * @author yong 2026-11-09
      */
    public function show(ShowCatalogRequest $request): Resource
    {
        return $this->success(
            $this->service->show($request->all()),
            CatalogResource::class
        );
    }

    /**
      * @desc Catalog添加
      * @author yong 2026-11-09
      */
    public function store(StoreCatalogRequest $request): Resource
    {
        return $this->success(
            $this->service->create($request->validated()),
            CatalogResource::class
        );
    }

    /**
      * @desc Catalog更新
      * @author yong 2026-11-09
      */
    public function update(Catalog $catalog, UpdateCatalogRequest $request): Resource
    {
        return $this->success(
            $this->service->update($catalog, $request->validated()),
            CatalogResource::class
        );
    }
}
