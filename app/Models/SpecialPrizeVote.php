<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialPrizeVote extends Model
{
    protected $fillable = ['special_prize_id', 'user_id', 'submission_id'];

    public function prize(): BelongsTo
    {
        return $this->belongsTo(SpecialPrize::class, 'special_prize_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }
}
