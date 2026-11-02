<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Request\Api\V1\AccountInsightRequest;
use App\Service\AccountInsightService;
use Goletter\Resource\Collection;
use Goletter\Resource\Resource;
use Hyperf\Di\Annotation\Inject;
use function Goletter\Utils\logging;

class AccountInsightController extends AbstractController
{
    #[Inject]
    private AccountInsightService $service;

    public function index(): Collection
    {
        return $this->collection($this->service->paginate($this->request->all()));
    }

    public function store(AccountInsightRequest $request): Resource
    {
        logging([...$request->all()], 'xxx', 'xxx');
        // $this->service->create($request->all());

        return $this->success();
    }
}
