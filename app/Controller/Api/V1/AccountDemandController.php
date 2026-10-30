<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Model\AccountDemand;
use App\Request\Api\V1\AccountDemandRequest;
use App\Resource\Api\V1\AccountDemandResource;
use App\Service\AccountDemandService;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;

class AccountDemandController extends AbstractController
{
    #[Inject]
    private AccountDemandService $service;

    public function index(): Collection
    {
        return $this->collection(
            $this->service->paginate($this->request->all()),
            AccountDemandResource::class
        );
    }

    public function store(AccountDemandRequest $request): Resource
    {
        return $this->success($this->service->create($request->all()), AccountDemandResource::class);
    }

    public function update(AccountDemand $accountDemand, AccountDemandRequest $request): Resource
    {
        return $this->success(
            $this->service->update($accountDemand, $request->validated()),
            AccountDemandResource::class
        );
    }
}