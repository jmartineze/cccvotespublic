<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contest extends Model
{
    use BelongsToTenant;

    protected $fillable = ['owner_id', 'name', 'description', 'cover_image', 'status', 'contest_type'];

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ContestCriterion::class)->orderBy('sort_order');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function isCharacterScenario(): bool
    {
        return $this->contest_type === 'character_scenario';
    }

    public function hasVotes(): bool
    {
        return Vote::whereHas('submission', fn ($q) => $q->where('contest_id', $this->id))->exists();
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image ? asset('storage/'.$this->cover_image) : null;
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'active' => ['label' => 'Active',  'class' => 'badge-active'],
            'closed' => ['label' => 'Closed',  'class' => 'badge-closed'],
            default => ['label' => 'Draft',   'class' => 'badge-draft'],
        };
    }
}
