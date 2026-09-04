<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if ($user->role === 'super_admin') {
            return;
        }

        // Admin view: only the tenant currently being administered.
        // Judge view: every tenant the user is a member of.
        $ids = $user->actingAsAdmin()
            ? array_filter([$user->currentTenantId()])
            : $user->memberTenantIds();

        $builder->whereIn($model->qualifyColumn('owner_id'), $ids ?: [0]);
    }
}
