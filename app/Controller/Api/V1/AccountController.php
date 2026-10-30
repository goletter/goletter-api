<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Service\AccountService;
use App\Service\Adv\AdvResolver;
use Goletter\Resource\Resource;
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
        // $this->service->detail($this->request->all())
        return $this->success($this->request->all());
    }
}
