<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if ($model->owner_id || ! auth()->check()) {
                return;
            }

            $user = auth()->user();

            if ($user->actingAsAdmin() && ! $user->isSuperAdmin()) {
                $model->owner_id = $user->currentTenantId();
            }
        });
    }
}
