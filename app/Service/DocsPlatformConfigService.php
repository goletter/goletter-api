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
use App\Model\DocsPlatformConfig;

class DocsPlatformConfigService extends BaseService
{
    public function paginate(array $params): LengthAwarePaginator
    {
        return DocsPlatformConfig::query()->filter($params)->paginate((int) Arr::get($params, 'pageSize', 20));
    }

    public function create(array $data)
    {
        return $this->save(new DocsPlatformConfig(), $data);
    }

    public function update(DocsPlatformConfig $docsPlatformConfig, array $data)
    {
        return $this->save($docsPlatformConfig, $data);
    }

    public function save(DocsPlatformConfig $docsPlatformConfig, array $data)
    {
        $docsPlatformConfig->fillDb($data);
        $docsPlatformConfig->save();

        return $docsPlatformConfig;
    }
}
