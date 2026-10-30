<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Service\AccountService;
use Hyperf\Di\Annotation\Inject;

class AccountController extends AbstractController
{
    #[Inject]
    private AccountService $service;

    public function index()
    {
        return $this->success();
    }

    public function detail()
    {
        return $this->success($this->service->detail($this->request->all()));
    }
}
