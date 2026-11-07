<?php

declare(strict_types=1);

namespace App\Logic\AccountInsight;

use App\Model\AccountInsight;
use Goletter\Server\BaseService;

/**
  * @desc 更新
  * @author yong 2026-11-08
  */
class UpdateLogic extends BaseService
{
    public function handle(AccountInsight $accountInsight, array $data)
    {
        $accountInsight->fillDb($data);
        $accountInsight->save();

        return $accountInsight;
    }
}
