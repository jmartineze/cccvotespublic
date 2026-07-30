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

        $tenantId = $user->role === 'tenant_admin' ? $user->id : $user->owner_id;

        $builder->where($model->qualifyColumn('owner_id'), $tenantId);
    }
}
