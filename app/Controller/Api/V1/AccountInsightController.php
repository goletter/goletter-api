<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Service\AccountInsightService;
use Goletter\Resource\Collection;
use Hyperf\Di\Annotation\Inject;

class AccountInsightController extends AbstractController
{
    #[Inject]
    private AccountInsightService $service;

    public function index(): Collection
    {
        return $this->collection($this->service->paginate($this->request->all()));
    }
}
