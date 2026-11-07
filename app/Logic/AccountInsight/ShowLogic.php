<?php

declare(strict_types=1);

namespace App\Logic\AccountInsight;

use App\Model\AccountInsight;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;

/**
  * @desc 详情
  * @author yong 2026-11-08
  */
class ShowLogic extends BaseService
{
    public function handle(array $data)
    {
        $id = (int) Arr::get($data, 'id');

        return AccountInsight::query()->where('id', $id)->first();
    }
}
