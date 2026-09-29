<?php

declare(strict_types=1);

namespace App\Model;

/**
 * 租户（共享库模式下的顶层隔离单位）。
 * 注意：不要 use BelongsToTenant。
 */
class Tenant extends Model
{
    protected ?string $table = 'tenants';

    protected array $fillable = [
        'name',
        'code',
        'status',
    ];

    protected array $casts = [
        'status' => 'integer',
    ];
}
