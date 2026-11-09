<?php

declare(strict_types=1);

namespace App\Service;

use App\Logic\Catalog\CatalogTrait;
use Goletter\Server\BaseService;
use Hyperf\Paginator\LengthAwarePaginator;

/**
  * @desc Catalog
  * @author yong 2026-11-09
  */
class CatalogService extends BaseService
{
    use CatalogTrait;

    /**
      * @desc 列表
      * @author yong 2026-11-09
      */
    public function paginate(array $params): LengthAwarePaginator
    {
        return $this->indexLogic->handle($params);
    }
}
