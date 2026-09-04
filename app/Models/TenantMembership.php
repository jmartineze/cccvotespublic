<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMembership extends Model
{
    protected $fillable = ['tenant_id', 'user_id', 'role'];

    public function isCoAdmin(): bool
    {
        return $this->role === 'co_admin';
    }

    /** The tenant admin who owns this membership. */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /** The judge who belongs to the tenant. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
