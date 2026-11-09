<?php

declare(strict_types=1);

namespace App\Logic\Account;

use Hyperf\Di\Annotation\Inject;

trait AccountTrait
{
    #[Inject]
    private IndexLogic $indexLogic;

    #[Inject]
    private DetailLogic $detailLogic;

    #[Inject]
    private CreateLogic $createLogic;

    #[Inject]
    private UpdateLogic $updateLogic;

    #[Inject]
    private ShowLogic $showLogic;

}
