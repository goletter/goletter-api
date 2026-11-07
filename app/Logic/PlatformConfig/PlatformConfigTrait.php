<?php

declare(strict_types=1);

namespace App\Logic\PlatformConfig;

use Hyperf\Di\Annotation\Inject;

trait PlatformConfigTrait
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
