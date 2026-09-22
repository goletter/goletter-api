<?php

declare(strict_types=1);

namespace App\Service\Action\Account;

use App\Model\Account;
use App\Support\TenantContext;
use Goletter\Server\BaseService;

class CreateAction extends BaseService
{
    public function handle(array $data)
    {
        // tenant_id 由 BelongsToTenant 在 creating 时从 TenantContext 自动写入
        $account = Account::query()->create([
            'business_id' => (int) ($data['business_id'] ?? 0),
            'name' => (string) ($data['name'] ?? ''),
            'status' => (int) ($data['status'] ?? 1),
        ]);

        return [
            'account' => $account->toArray(),
            'tenant_id' => TenantContext::id(),
        ];
    }
}
