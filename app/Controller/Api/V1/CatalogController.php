<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Request\Api\V1\Catalog\IndexCatalogRequest;
use App\Resource\Api\V1\CatalogResource;
use App\Service\CatalogService;
use Goletter\Resource\Collection;
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
}
