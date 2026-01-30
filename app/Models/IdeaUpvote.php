<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaUpvote extends Model
{
    protected $fillable = [
        'idea_id',
        'user_id',
    ];

    // Relationships
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
