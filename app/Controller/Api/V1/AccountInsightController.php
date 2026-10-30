<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AbstractController;
use App\Service\AccountInsightService;
use Hyperf\Di\Annotation\Inject;

class AccountInsightController extends AbstractController
{
    #[Inject]
    private AccountInsightService $service;

    public function index()
    {
        return $this->success();
    }
}
