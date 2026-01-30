<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    protected $fillable = [
        'event_type',
        'event_category',
        'user_id',
        'event_data',
        'ip_address',
        'user_agent',
        'session_id',
        'occurred_at',
    ];

    protected $casts = [
        'event_data' => 'array',
        'occurred_at' => 'datetime',
    ];

    /**
     * Get the user that owns the analytics event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by event type.
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope for filtering by event category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('event_category', $category);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Get event data by key.
     */
    public function getEventData(string $key, $default = null)
    {
        return data_get($this->event_data, $key, $default);
    }

    /**
     * Set event data by key.
     */
    public function setEventData(string $key, $value): self
    {
        $data = $this->event_data ?? [];
        data_set($data, $key, $value);
        $this->event_data = $data;

        return $this;
    }
}
