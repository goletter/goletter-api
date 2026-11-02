<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Service;

use Goletter\Server\BaseService;
use Hyperf\Collection\Arr;
use Hyperf\Paginator\LengthAwarePaginator;
use App\Model\PlatformConfig;

class PlatformConfigService extends BaseService
{
    public function paginate(array $params): LengthAwarePaginator
    {
        return PlatformConfig::query()->filter($params)->paginate((int) Arr::get($params, 'pageSize', 20));
    }

    public function create(array $data)
    {
        return $this->save(new PlatformConfig(), $data);
    }

    public function update(PlatformConfig $platformConfig, array $data)
    {
        return $this->save($platformConfig, $data);
    }

    public function save(PlatformConfig $platformConfig, array $data)
    {
        $platformConfig->fillDb($data);
        $platformConfig->save();

        return $platformConfig;
    }
}
