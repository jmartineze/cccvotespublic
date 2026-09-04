<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'owner_id', 'username', 'judge_mode'];

    protected $hidden = ['password', 'remember_token'];

    /** Memoised tenant-id lists. */
    protected ?array $memberTenantIdsCache = null;

    protected ?array $adminTenantIdsCache = null;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'judge_mode' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // A judge's "home" tenant (owner_id) is always also a membership.
        static::created(function (User $user) {
            if ($user->role === 'judge' && $user->owner_id) {
                TenantMembership::firstOrCreate(
                    ['tenant_id' => $user->owner_id, 'user_id' => $user->id],
                    ['role' => 'judge'],
                );
            }
        });
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function contests(): HasMany
    {
        return $this->hasMany(Contest::class, 'owner_id');
    }

    /** Rows linking this judge to the tenants they participate in. */
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class, 'user_id');
    }

    /** Tenants this judge belongs to. */
    public function memberTenants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships', 'user_id', 'tenant_id')
            ->withPivot('role')->withTimestamps();
    }

    /** Judges that belong to this tenant admin. */
    public function tenantJudges(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships', 'tenant_id', 'user_id')
            ->withPivot('role')->withTimestamps();
    }

    public function hasVotedOn(int $submissionId): bool
    {
        return $this->votes()->where('submission_id', $submissionId)->exists();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === 'tenant_admin';
    }

    /** Co-admin of at least one tenant. */
    public function isCoAdmin(): bool
    {
        return ! $this->isTenantAdmin() && ! $this->isSuperAdmin() && $this->adminTenantIds() !== [];
    }

    public function isCoAdminOf(?int $tenantId): bool
    {
        return $tenantId !== null && in_array($tenantId, $this->adminTenantIds(), true);
    }

    /** Only true for a plain judge with no co-admin powers anywhere. */
    public function isJudge(): bool
    {
        return $this->role === 'judge';
    }

    /** Has an admin-capable role, regardless of the current view mode. */
    public function isAnyAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->isTenantAdmin() || $this->isCoAdmin();
    }

    /** Can flip between the admin panel and the judge view. */
    public function canSwitchMode(): bool
    {
        return $this->isTenantAdmin() || $this->adminTenantIds() !== [];
    }

    public function inJudgeMode(): bool
    {
        return $this->canSwitchMode() && $this->judge_mode;
    }

    /** Effective right now: sees the admin panel, cannot vote. */
    public function actingAsAdmin(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->canSwitchMode() && ! $this->judge_mode && $this->currentTenantId() !== null;
    }

    /** Effective right now: browses/votes like a judge. */
    public function actingAsJudge(): bool
    {
        return ! $this->isSuperAdmin() && ! $this->actingAsAdmin();
    }

    /** Every tenant id whose contests this user may see as a member (judge view). */
    public function memberTenantIds(): array
    {
        return $this->memberTenantIdsCache ??= $this->isTenantAdmin()
            ? [$this->id]
            : ($this->isSuperAdmin() ? [] : $this->memberships()->pluck('tenant_id')->all());
    }

    /** Tenants this user can administer. */
    public function adminTenantIds(): array
    {
        return $this->adminTenantIdsCache ??= $this->isTenantAdmin()
            ? [$this->id]
            : ($this->isSuperAdmin() ? [] : $this->memberships()->where('role', 'co_admin')->pluck('tenant_id')->all());
    }

    /**
     * The tenant the user is currently administering. A tenant_admin owns one;
     * a multi-tenant co-admin picks via the nav switcher (session), defaulting
     * to their first admin tenant.
     */
    public function currentTenantId(): ?int
    {
        if ($this->isTenantAdmin()) {
            return $this->id;
        }

        $adminTenants = $this->adminTenantIds();

        if ($adminTenants === []) {
            return null;
        }

        $chosen = session('admin_tenant_id');

        return in_array($chosen, $adminTenants, true) ? $chosen : $adminTenants[0];
    }

    /** Alias used where "the tenant to attribute new records to" is meant. */
    public function tenantId(): ?int
    {
        return $this->currentTenantId();
    }

    public function belongsToTenant(?int $tenantId): bool
    {
        return $tenantId !== null && in_array($tenantId, $this->memberTenantIds(), true);
    }

    public function scopeJudgesOf($query, int $ownerId)
    {
        return $query->where('role', 'judge')->where('owner_id', $ownerId);
    }
}
