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
use App\Model\AccountDemand;
use App\Request\Api\V1\AccountDemandRequest;
use App\Resource\Api\V1\AccountDemandResource;
use App\Service\AccountDemandService;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

/**
 * 需求管理.
 */
class AccountDemandController extends AbstractController
{
    #[Inject]
    private AccountDemandService $service;

    /**
     * @desc 需求列表
     */
    public function index(): Collection
    {
        return $this->collection(
            $this->service->paginate($this->request->all()),
            AccountDemandResource::class
        );
    }

    /**
     * @desc 需求添加
     */
    public function store(AccountDemandRequest $request): Resource
    {
        return $this->success($this->service->create($request->all()), AccountDemandResource::class);
    }

    /**
     * @desc 需求更新
     */
    public function update(AccountDemand $accountDemand, AccountDemandRequest $request): Resource
    {
        return $this->success(
            $this->service->update($accountDemand, $request->validated()),
            AccountDemandResource::class
        );
    }
}
