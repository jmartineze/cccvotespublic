<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vote extends Model
{
    protected $fillable = [
        'user_id', 'submission_id', 'total_score', 'comment',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function voteScores(): HasMany
    {
        return $this->hasMany(VoteScore::class);
    }

    public function recalculateTotalScore(): void
    {
        $this->update(['total_score' => $this->voteScores()->sum('score')]);
    }
}
