<?php

declare(strict_types=1);

namespace App\Model;

use Goletter\Traits\BelongsToTenant;

class Account extends Model
{
    use BelongsToTenant;
}
