<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserEngagementMetric extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'login_count',
        'ideas_created',
        'suggestions_submitted',
        'upvotes_given',
        'upvotes_received',
        'collaborations_joined',
        'comments_made',
        'notifications_read',
        'time_spent_minutes',
        'points_earned',
        'engagement_score',
    ];

    protected $casts = [
        'date' => 'date',
        'engagement_score' => 'decimal:2',
    ];

    /**
     * Get the user that owns the engagement metrics.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate engagement score based on various metrics.
     */
    public function calculateEngagementScore(): float
    {
        // Weighted scoring algorithm
        $score = 0;

        // Login activity (20% weight)
        $score += min($this->login_count * 2, 20);

        // Content creation (30% weight)
        $score += min(($this->ideas_created * 5) + ($this->suggestions_submitted * 2), 30);

        // Social engagement (25% weight)
        $score += min(($this->upvotes_given * 0.5) + ($this->upvotes_received * 1) + ($this->collaborations_joined * 3), 25);

        // Interaction (15% weight)
        $score += min(($this->comments_made * 1) + ($this->notifications_read * 0.2), 15);

        // Time spent (10% weight)
        $score += min($this->time_spent_minutes * 0.1, 10);

        return round($score, 2);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for ordering by engagement score.
     */
    public function scopeOrderByEngagementScore($query, $direction = 'desc')
    {
        return $query->orderBy('engagement_score', $direction);
    }
}
