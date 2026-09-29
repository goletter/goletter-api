<?php

declare(strict_types=1);

namespace App\Model\Concerns;

use App\Support\TenantContext;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Events\Creating;

/**
 * 共享库多租户：自动 where tenant_id + 创建时写入租户。
 *
 * 使用：业务 Model 加 `use BelongsToTenant;`
 * Tenant 表本身不要用此 Trait。
 *
 * 注意：若 Model 自己定义了 creating()，会覆盖 Trait 方法，需自行合并逻辑。
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = TenantContext::id();
            if ($tenantId !== null) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }

    public function creating(Creating $event): void
    {
        $model = $event->getModel();
        if (empty($model->tenant_id)) {
            $model->tenant_id = TenantContext::idOrFail();
        }
    }

    /**
     * 跨租户查询（仅超管/内部任务显式调用）。
     */
    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * 指定租户查询（忽略当前 Context）。
     */
    public function scopeForTenant(Builder $query, int $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')
            ->where($query->getModel()->getTable() . '.tenant_id', $tenantId);
    }
}
