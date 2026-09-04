<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpecialPrize extends Model
{
    protected $fillable = ['contest_id', 'name', 'description', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    public function contest(): BelongsTo
    {
        return $this->belongsTo(Contest::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(SpecialPrizeVote::class);
    }
}
