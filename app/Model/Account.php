<?php

declare(strict_types=1);

namespace App\Model;

use App\Model\Concerns\BelongsToTenant;

class Account extends Model
{
    use BelongsToTenant;

    protected ?string $table = 'accounts';

    protected array $fillable = [
        'tenant_id',
        'business_id',
        'name',
        'status',
    ];

    protected array $casts = [
        'tenant_id' => 'integer',
        'business_id' => 'integer',
        'status' => 'integer',
    ];
}
