<?php

declare(strict_types=1);

namespace App\Logic\PlatformConfig;

use App\Model\PlatformConfig;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;

/**
  * @desc 列表
  * @author yong 2026-11-07
  */
class IndexLogic extends BaseService
{
    public function handle(array $params)
    {
        return PlatformConfig::query()->filter($params)->paginate((int) Arr::get($params, 'pageSize', 20));
    }
}
