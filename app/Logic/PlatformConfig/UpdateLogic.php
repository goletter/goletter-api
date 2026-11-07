<?php

declare(strict_types=1);

namespace App\Logic\PlatformConfig;

use App\Model\PlatformConfig;
use Goletter\Server\BaseService;

/**
  * @desc 更新
  * @author yong 2026-11-07
  */
class UpdateLogic extends BaseService
{
    public function handle(PlatformConfig $platformConfig, array $data)
    {
        $platformConfig->fillDb($data);
        $platformConfig->save();

        return $platformConfig;
    }
}
