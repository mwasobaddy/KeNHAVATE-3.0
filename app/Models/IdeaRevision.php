<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaRevision extends Model
{
    protected $fillable = [
        'idea_id',
        'user_id',
        'revision_number',
        'changes',
        'change_summary',
        'previous_data',
        'new_data',
    ];

    protected $casts = [
        'changes' => 'array',
        'previous_data' => 'array',
        'new_data' => 'array',
        'revision_number' => 'integer',
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

    // Scopes
    public function scopeByRevisionNumber($query, $number)
    {
        return $query->where('revision_number', $number);
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('revision_number', 'desc');
    }

    // Helper methods
    public function getChangedFields(): array
    {
        return array_keys($this->changes ?? []);
    }

    public function hasFieldChanged(string $field): bool
    {
        return isset($this->changes[$field]);
    }

    public function getPreviousValue(string $field)
    {
        return $this->previous_data[$field] ?? null;
    }

    public function getNewValue(string $field)
    {
        return $this->new_data[$field] ?? null;
    }
}
