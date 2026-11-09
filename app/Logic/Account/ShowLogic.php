<?php

declare(strict_types=1);

namespace App\Logic\Account;

use App\Model\Account;
use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;

/**
  * @desc 详情
  * @author yong 2026-11-09
  */
class ShowLogic extends BaseService
{
    public function handle(array $data)
    {
        $id = Arr::get($data, 'id');

        return Account::query()->where('id', $id)->first();
    }
}
