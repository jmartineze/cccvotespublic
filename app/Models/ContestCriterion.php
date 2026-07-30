<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContestCriterion extends Model
{
    protected $fillable = [
        'contest_id', 'name', 'description', 'max_score', 'sort_order', 'tiebreak_order', 'color',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'integer',
            'sort_order' => 'integer',
            'tiebreak_order' => 'integer',
        ];
    }

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function voteScores(): HasMany
    {
        return $this->hasMany(VoteScore::class);
    }

    public function scopeTiebreakOrdered(Builder $query): Builder
    {
        return $query->whereNotNull('tiebreak_order')->orderBy('tiebreak_order');
    }
}
