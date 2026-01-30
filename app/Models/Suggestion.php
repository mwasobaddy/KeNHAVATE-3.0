<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Suggestion extends Model
{
    protected $fillable = [
        'idea_id',
        'author_id',
        'parent_id',
        'content',
        'type',
        'is_accepted',
        'is_rejected',
        'accepted_by',
        'accepted_at',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
        'is_rejected' => 'boolean',
        'accepted_at' => 'datetime',
    ];

    // Relationships
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Suggestion::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Suggestion::class, 'parent_id');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    // Scopes
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeAccepted($query)
    {
        return $query->where('is_accepted', true);
    }

    public function scopeRejected($query)
    {
        return $query->where('is_rejected', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePending($query)
    {
        return $query->where('is_accepted', false)->where('is_rejected', false);
    }

    // Helper methods
    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
    }

    public function isAccepted(): bool
    {
        return $this->is_accepted;
    }

    public function isRejected(): bool
    {
        return $this->is_rejected;
    }

    public function isPending(): bool
    {
        return ! $this->is_accepted && ! $this->is_rejected;
    }

    public function canBeAcceptedBy(User $user): bool
    {
        return $this->idea->author_id === $user->id && $this->isPending();
    }

    public function canBeRejectedBy(User $user): bool
    {
        return $this->idea->author_id === $user->id && $this->isPending();
    }

    public function accept(User $user): void
    {
        $this->update([
            'is_accepted' => true,
            'is_rejected' => false,
            'accepted_by' => $user->id,
            'accepted_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_accepted' => false,
            'is_rejected' => true,
        ]);
    }

    public function getTotalReplies(): int
    {
        return $this->replies()->count();
    }

    public function getThreadDepth(): int
    {
        $depth = 0;
        $current = $this;

        while ($current->parent) {
            $depth++;
            $current = $current->parent;
        }

        return $depth;
    }
}
