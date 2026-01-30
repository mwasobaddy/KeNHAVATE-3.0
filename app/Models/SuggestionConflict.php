<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuggestionConflict extends Model
{
    protected $fillable = [
        'idea_id',
        'suggestion_1_id',
        'suggestion_2_id',
        'conflict_type',
        'field_name',
        'conflict_description',
        'conflicting_values',
        'resolution_status',
        'resolved_by',
        'resolution_notes',
        'resolved_at',
    ];

    protected $casts = [
        'conflicting_values' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function suggestion1(): BelongsTo
    {
        return $this->belongsTo(Suggestion::class, 'suggestion_1_id');
    }

    public function suggestion2(): BelongsTo
    {
        return $this->belongsTo(Suggestion::class, 'suggestion_2_id');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->resolution_status === 'resolved';
    }

    public function resolve(User $user, ?string $resolutionNotes = null): void
    {
        $this->update([
            'resolution_status' => 'resolved',
            'resolved_by' => $user->id,
            'resolution_notes' => $resolutionNotes,
            'resolved_at' => now(),
        ]);
    }
}
