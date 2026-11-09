<?php

declare(strict_types=1);

namespace App\Logic\Busine;

use Hyperf\Di\Annotation\Inject;

trait BusineTrait
{
    #[Inject]
    private IndexLogic $indexLogic;

    #[Inject]
    private UserLogic $userLogic;

    #[Inject]
    private SystemUserLogic $systemUserLogic;

    #[Inject]
    private PendingUserLogic $pendingUserLogic;

    #[Inject]
    private AccountLogic $accountLogic;
}
