<?php

declare(strict_types=1);

namespace App\Logic\PlatformConfig;

use App\Model\PlatformConfig;
use Goletter\Server\BaseService;

/**
  * @desc 创建
  * @author yong 2026-11-07
  */
class CreateLogic extends BaseService
{
    public function handle(array $data)
    {
        $platformConfig = new PlatformConfig();
        $platformConfig->fillDb($data);
        $platformConfig->save();

        return $platformConfig;
    }
}
