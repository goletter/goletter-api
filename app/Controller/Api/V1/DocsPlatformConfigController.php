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

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Model\DocsPlatformConfig;
use App\Service\DocsPlatformConfigService;
use Goletter\Resource\Collection;
use Hyperf\Di\Annotation\Inject;

class DocsPlatformConfigController extends AbstractController
{
    #[Inject]
    private DocsPlatformConfigService $service;

    /**
     * @return Collection
     */
    public function index()
    {
        return $this->collection($this->service->paginate($this->request->all()));
    }

    /**
     * @return \Goletter\Resource\Resource
     */
    public function store()
    {
        return $this->success($this->service->create($this->request->all()));
    }

    /**
     * @return \Goletter\Resource\Resource
     */
    public function update(DocsPlatformConfig $docsPlatformConfig)
    {
        return $this->success($this->service->update($docsPlatformConfig, $this->request->all()));
    }
}
