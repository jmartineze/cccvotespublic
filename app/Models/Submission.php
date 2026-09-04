<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Submission extends Model
{
    protected $fillable = [
        'contest_id', 'discord_user', 'character_name',
        'country', 'backstory', 'scenario_description', 'gender', 'style',
    ];

    protected $casts = [
        'contest_id' => 'integer',
    ];

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(SubmissionImage::class)->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function specialPrizeVotes(): HasMany
    {
        return $this->hasMany(SpecialPrizeVote::class);
    }

    public function getCategoryAttribute(): string
    {
        return $this->gender.' '.$this->style;
    }

    public function getTotalVotesScoreAttribute(): int
    {
        return $this->votes()->sum('total_score');
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $image = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();

        return $image?->url;
    }

    /**
     * Returns the first non-video image, or null if all files are videos.
     */
    public function thumbnailImage(): ?SubmissionImage
    {
        if ($this->relationLoaded('images')) {
            return $this->images->first(fn ($img) => ! $img->isVideo());
        }

        return $this->images()->get()->first(fn ($img) => ! $img->isVideo());
    }

    public function hasVoteFrom(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }

    public function getVoteFrom(int $userId): ?Vote
    {
        return $this->votes()->where('user_id', $userId)->first();
    }
}
