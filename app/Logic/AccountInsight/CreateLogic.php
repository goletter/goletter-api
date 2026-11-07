<?php

declare(strict_types=1);

namespace App\Logic\AccountInsight;

use App\Model\AccountInsight;
use Goletter\Server\BaseService;

/**
  * @desc 创建
  * @author yong 2026-11-08
  */
class CreateLogic extends BaseService
{
    public function handle(array $data)
    {
        $accountInsight = new AccountInsight();
        $accountInsight->fillDb($data);
        $accountInsight->save();

        return $accountInsight;
    }
}
