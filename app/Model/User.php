<?php

declare(strict_types=1);

namespace App\Model;

use Qbhy\HyperfAuth\AuthAbility;
use Qbhy\HyperfAuth\Authenticatable;

class User extends Model implements Authenticatable
{
    use AuthAbility;
}