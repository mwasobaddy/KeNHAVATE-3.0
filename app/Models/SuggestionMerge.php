<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuggestionMerge extends Model
{
    protected $fillable = [
        'idea_id',
        'merged_by',
        'merged_suggestions',
        'merge_summary',
        'changes_applied',
        'merge_type',
        'has_conflicts',
        'conflict_resolution',
    ];

    protected $casts = [
        'merged_suggestions' => 'array',
        'changes_applied' => 'array',
        'conflict_resolution' => 'array',
        'has_conflicts' => 'boolean',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function mergedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    public function getMergedSuggestions()
    {
        return Suggestion::whereIn('id', $this->merged_suggestions)->get();
    }
}
