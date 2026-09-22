<?php

declare(strict_types=1);

namespace App\Job;

use App\Model\Account;
use function Goletter\Utils\logging;

/**
 * 示例：按租户处理账号（配合 pushSerial('tenant:'.$tenantId.':biz:'.$businessId, ...)）。
 */
class HandleAccountJob extends TenantJob
{
    public function __construct(
        int $tenantId,
        public int $accountId,
    ) {
        parent::__construct($tenantId);
    }

    protected function handleForTenant(): void
    {
        $account = Account::query()->find($this->accountId);
        logging([
            'tenant_id' => $this->tenantId,
            'account' => $account?->toArray(),
        ], 'HandleAccountJob', 'tenant');
    }
}
