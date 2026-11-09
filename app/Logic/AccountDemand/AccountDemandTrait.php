<?php

declare(strict_types=1);

namespace App\Logic\AccountDemand;

use Hyperf\Di\Annotation\Inject;

trait AccountDemandTrait
{
    #[Inject]
    private IndexLogic $indexLogic;

    #[Inject]
    private CreateLogic $createLogic;

    #[Inject]
    private UpdateLogic $updateLogic;

    #[Inject]
    private BatchUpdateLogic $batchUpdateLogic;

    #[Inject]
    private ShowLogic $showLogic;

}
