<?php

declare(strict_types=1);

namespace App\Model;

use Goletter\ModelFilter\Filterable;
use Goletter\Traits\BatchUpdateOrCreateTrait;
use Goletter\Traits\BatchUpdateTrait;

class AccountInsight extends Model
{
    use Filterable;
    use BatchUpdateTrait;
    use BatchUpdateOrCreateTrait;
}
