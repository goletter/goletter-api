<?php
namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Model\AccountDemand;
use App\Service\AccountDemandService;
use Goletter\Resource\Collection;
use Hyperf\Di\Annotation\Inject;

class AccountDemandController extends AbstractController
{
    #[Inject]
    private AccountDemandService $service;

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
    public function update(AccountDemand $accountDemand)
    {
        return $this->success($this->service->update($accountDemand, $this->request->all()));
    }
}