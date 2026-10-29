<?php

declare(strict_types=1);

namespace App\Controller\Admin\V1;

use App\Controller\AbstractController;

class AccountDemandController extends AbstractController
{
    public function index()
    {
        return $this->success();
    }
}
