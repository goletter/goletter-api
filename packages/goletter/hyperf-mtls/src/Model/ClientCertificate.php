<?php

declare(strict_types=1);

namespace Goletter\Mtls\Model;

use Hyperf\DbConnection\Model\Model;

class ClientCertificate extends Model
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_REVOKED = 'revoked';

    protected ?string $table = 'client_certificates';

    protected array $fillable = [
        'user',
        'cn',
        'serial',
        'fingerprint',
        'status',
        'cert_path',
        'key_path',
        'p12_path',
        'pfx_path',
        'issued_at',
        'expires_at',
        'revoked_at',
        'revoked_reason',
    ];

    protected array $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];
}
