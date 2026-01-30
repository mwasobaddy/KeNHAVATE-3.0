<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    protected $fillable = [
        'type',
        'title',
        'message',
        'data',
        'user_id',
        'sender_id',
        'notifiable_id',
        'notifiable_type',
        'is_read',
        'is_email_sent',
        'read_at',
        'email_sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'is_email_sent' => 'boolean',
        'read_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user that sent the notification.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Get the notifiable model (idea, suggestion, etc.).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): void
    {
        if (! $this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark the notification as email sent.
     */
    public function markEmailAsSent(): void
    {
        if (! $this->is_email_sent) {
            $this->update([
                'is_email_sent' => true,
                'email_sent_at' => now(),
            ]);
        }
    }

    /**
     * Scope for unread notifications.
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for read notifications.
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope for notifications of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the URL for the notification.
     */
    public function getUrlAttribute(): string
    {
        return match ($this->type) {
            'suggestion_created' => route('ideas.suggestions.index', $this->notifiable_id),
            'suggestion_accepted' => route('ideas.suggestions.index', $this->notifiable?->idea_id),
            'idea_upvoted' => route('ideas.show', $this->notifiable_id),
            'collaborator_joined' => route('ideas.show', $this->notifiable_id),
            default => '#',
        };
    }
}
