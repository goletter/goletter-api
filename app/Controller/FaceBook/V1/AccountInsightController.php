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

namespace App\Controller\FaceBook\V1;

use App\Controller\AbstractController;
use App\Request\FaceBook\V1\AccountInsight\StoreAccountInsightRequest;
use App\Service\AccountInsightService;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

/**
  * @desc 消耗管理
  * @author yong 2026-11-03
  */
class AccountInsightController extends AbstractController
{
    #[Inject]
    private AccountInsightService $service;

    /**
      * @desc 消耗列表
      * @author yong 2026-11-03
      */
    public function index(): Collection
    {
        return $this->collection($this->service->paginate($this->request->all()));
    }

    /**
      * @desc 消耗获取
      * @author yong 2026-11-03
      */
    public function store(StoreAccountInsightRequest $request): Resource
    {
        $this->service->create($request->all());

        return $this->success();
    }
}
