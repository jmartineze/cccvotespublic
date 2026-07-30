<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionImage extends Model
{
    protected $fillable = ['submission_id', 'image_path', 'sort_order'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class);
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/'.$this->image_path);
    }

    public function isVideo(): bool
    {
        $ext = strtolower(pathinfo($this->image_path, PATHINFO_EXTENSION));

        return in_array($ext, ['mp4', 'webm', 'mov', 'avi']);
    }
}
