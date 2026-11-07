<?php

declare(strict_types=1);

namespace App\Logic\AccountInsight;

use Hyperf\Di\Annotation\Inject;

trait AccountInsightTrait
{
    #[Inject]
    private IndexLogic $indexLogic;

    #[Inject]
    private ShowLogic $showLogic;

    #[Inject]
    private CreateLogic $createLogic;

    #[Inject]
    private UpdateLogic $updateLogic;

}
