<?php

namespace App\Logic\AccountDemand;

use App\Logic\Account\DetailLogic;
use App\Logic\Account\IndexLogic;
use Hyperf\Di\Annotation\Inject;

trait AccountDemandTrait
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
    private BatchUpdateLogic $batchUpdateLogic;
}