<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteScore extends Model
{
    protected $fillable = ['vote_id', 'contest_criterion_id', 'score'];

    protected function casts(): array
    {
        return ['score' => 'integer'];
    }

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(ContestCriterion::class, 'contest_criterion_id');
    }
}
