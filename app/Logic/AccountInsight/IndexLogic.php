<?php

declare(strict_types=1);

namespace App\Logic\AccountInsight;

use App\Model\AccountInsight;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;

/**
  * @desc 列表
  * @author yong 2026-11-08
  */
class IndexLogic extends BaseService
{
    public function handle(array $params)
    {
        return AccountInsight::query()->filter($params)->paginate((int) Arr::get($params, 'pageSize', 20));
    }
}
