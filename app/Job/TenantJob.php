<?php

declare(strict_types=1);

namespace App\Job;

use App\Support\TenantContext;
use Hyperf\AsyncQueue\Job;

/**
 * 租户感知 Job 基类：消费时恢复 tenant_id，查询自动带 Scope。
 */
abstract class TenantJob extends Job
{
    public function __construct(public int $tenantId)
    {
    }

    final public function handle(): void
    {
        TenantContext::run($this->tenantId, function () {
            $this->handleForTenant();
        });
    }

    abstract protected function handleForTenant(): void;
}
