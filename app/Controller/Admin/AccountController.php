<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractController;
use App\Model\Account;

class AccountController extends AbstractController
{
    public function index()
    {
        $accounts = Account::query()->get();

        return $this->collection($accounts);
    }
}
