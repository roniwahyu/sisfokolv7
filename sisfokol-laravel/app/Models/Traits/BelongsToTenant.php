<?php

namespace App\Models\Traits;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * ADR-003: Global scope tenant_id + auto-fill on create.
 * Trait ini WAJIB di-use di SEMUA model domain (bukan tenant itu sendiri).
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $ctx = app(TenantContext::class);
            if ($ctx->isInitialized()) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $ctx->id);
            }
            // superadmin context (uninitialized) → no scope (sees all)
        });

        static::creating(function (Model $model) {
            $ctx = app(TenantContext::class);
            if ($ctx->isInitialized() && empty($model->tenant_id)) {
                $model->tenant_id = $ctx->id;
            }
        });
    }
}
