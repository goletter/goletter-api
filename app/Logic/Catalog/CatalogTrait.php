<?php

declare(strict_types=1);

namespace App\Logic\Catalog;

use Hyperf\Di\Annotation\Inject;

trait CatalogTrait
{
    #[Inject]
    private IndexLogic $indexLogic;
}
