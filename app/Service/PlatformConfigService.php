<?php

declare(strict_types=1);

namespace App\Service;

use App\Logic\PlatformConfig\PlatformConfigTrait;
use App\Model\PlatformConfig;
use Goletter\Server\BaseService;

/**
  * @desc PlatformConfig
  * @author yong 2026-11-07
  */
class PlatformConfigService extends BaseService
{
    use PlatformConfigTrait;

    /**
      * @desc 列表
      * @author yong 2026-11-07
      */
    public function paginate(array $params)
    {
        return $this->indexLogic->handle($params);
    }

    /**
      * @desc 详情
      * @author yong 2026-11-07
      */
    public function show(array $data)
    {
        return $this->showLogic->handle($data);
    }

    /**
      * @desc 创建
      * @author yong 2026-11-07
      */
    public function create(array $data)
    {
        return $this->createLogic->handle($data);
    }

    /**
      * @desc 更新
      * @author yong 2026-11-07
      */
    public function update(PlatformConfig $platformConfig, array $data)
    {
        return $this->updateLogic->handle($platformConfig, $data);
    }
}
