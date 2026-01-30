<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdeaLifecycleAnalytic extends Model
{
    protected $fillable = [
        'idea_id',
        'idea_created_at',
        'submitted_at',
        'approved_at',
        'rejected_at',
        'first_collaborator_joined_at',
        'first_suggestion_at',
        'last_activity_at',
        'total_suggestions',
        'accepted_suggestions',
        'rejected_suggestions',
        'total_upvotes',
        'unique_contributors',
        'total_collaborators',
        'merge_operations',
        'conflict_resolutions',
        'collaboration_rate',
        'acceptance_rate',
        'time_to_first_collaboration_hours',
        'time_to_submission_hours',
        'total_lifecycle_days',
        'current_status',
    ];

    protected $casts = [
        'idea_created_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'first_collaborator_joined_at' => 'datetime',
        'first_suggestion_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'collaboration_rate' => 'decimal:2',
        'acceptance_rate' => 'decimal:2',
    ];

    /**
     * Get the idea that owns the lifecycle analytics.
     */
    public function idea(): BelongsTo
    {
        return $this->belongsTo(Idea::class);
    }

    /**
     * Calculate collaboration rate.
     */
    public function calculateCollaborationRate(): float
    {
        if ($this->total_suggestions === 0) {
            return 0;
        }

        return round(($this->unique_contributors / $this->total_suggestions) * 100, 2);
    }

    /**
     * Calculate acceptance rate.
     */
    public function calculateAcceptanceRate(): float
    {
        if ($this->total_suggestions === 0) {
            return 0;
        }

        return round(($this->accepted_suggestions / $this->total_suggestions) * 100, 2);
    }

    public function calculateTimeToFirstCollaboration(): ?int
    {
        if (! $this->first_collaborator_joined_at) {
            return null;
        }

        return $this->idea_created_at->diffInHours($this->first_collaborator_joined_at);
    }

    /**
     * Calculate time to submission in hours.
     */
    public function calculateTimeToSubmission(): ?int
    {
        if (! $this->submitted_at) {
            return null;
        }

        return $this->idea_created_at->diffInHours($this->submitted_at);
    }

    /**
     * Calculate total lifecycle in days.
     */
    public function calculateTotalLifecycleDays(): ?int
    {
        $endDate = $this->approved_at ?? $this->rejected_at ?? $this->last_activity_at ?? now();

        return $this->idea_created_at->diffInDays($endDate);
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('current_status', $status);
    }

    /**
     * Scope for ordering by lifecycle duration.
     */
    public function scopeOrderByLifecycleDuration($query, $direction = 'asc')
    {
        return $query->orderBy('total_lifecycle_days', $direction);
    }
}
