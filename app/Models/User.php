<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'owner_id', 'username'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function contests(): HasMany
    {
        return $this->hasMany(Contest::class, 'owner_id');
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

    public function isJudge(): bool
    {
        return $this->role === 'judge';
    }

    public function isAnyAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'tenant_admin'], true);
    }

    public function scopeJudgesOf($query, int $ownerId)
    {
        return $query->where('role', 'judge')->where('owner_id', $ownerId);
    }
}
