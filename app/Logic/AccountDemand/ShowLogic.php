<?php

declare(strict_types=1);

namespace App\Logic\AccountDemand;

use App\Model\AccountDemand;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;

/**
  * @desc 详情
  * @author yong 2026-11-07
  */
class ShowLogic extends BaseService
{
    public function handle(array $data)
    {
        $id = (int) Arr::get($data, 'id');

        return AccountDemand::query()->where('id', $id)->first();
    }
}
