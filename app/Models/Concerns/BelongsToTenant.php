<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (! $model->owner_id && auth()->check() && auth()->user()->role === 'tenant_admin') {
                $model->owner_id = auth()->id();
            }
        });
    }
}
