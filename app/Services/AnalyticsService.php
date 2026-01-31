<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Idea;
use App\Models\IdeaLifecycleAnalytic;
use App\Models\Suggestion;
use App\Models\User;
use App\Models\UserEngagementMetric;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Track an analytics event.
     */
    public function trackEvent(
        string $eventType,
        string $eventCategory,
        ?User $user = null,
        ?array $eventData = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $sessionId = null
    ): AnalyticsEvent {
        return AnalyticsEvent::create([
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'user_id' => $user?->id,
            'event_data' => $eventData,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'session_id' => $sessionId,
            'occurred_at' => now(),
        ]);
    }

    /**
     * Get user engagement dashboard data.
     */
    public function getUserEngagementDashboard(?User $user = null, int $days = 30): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        if ($user) {
            return $this->getUserEngagementMetrics($user, $startDate, $endDate);
        }

        return $this->getSystemEngagementMetrics($startDate, $endDate);
    }

    /**
     * Get individual user engagement metrics.
     */
    private function getUserEngagementMetrics(User $user, Carbon|CarbonImmutable $startDate, Carbon|CarbonImmutable $endDate): array
    {
        $days = $startDate->diffInDays($endDate) + 1; // +1 to include both start and end dates

        $metrics = UserEngagementMetric::where('user_id', $user->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get();

        $events = AnalyticsEvent::where('user_id', $user->id)
            ->whereBetween('occurred_at', [$startDate, $endDate])
            ->get();

        return [
            'user' => $user,
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            'metrics' => [
                'total_logins' => $metrics->sum('login_count'),
                'total_ideas_created' => $metrics->sum('ideas_created'),
                'total_suggestions' => $metrics->sum('suggestions_submitted'),
                'total_upvotes_given' => $metrics->sum('upvotes_given'),
                'total_upvotes_received' => $metrics->sum('upvotes_received'),
                'total_collaborations' => $metrics->sum('collaborations_joined'),
                'total_points_earned' => $metrics->sum('points_earned'),
                'avg_engagement_score' => $metrics->avg('engagement_score'),
                'total_time_spent' => $metrics->sum('time_spent_minutes'),
            ],
            'trends' => [
                'daily_engagement' => $metrics->pluck('engagement_score', 'date'),
                'activity_timeline' => $this->buildActivityTimeline($events),
            ],
            'insights' => $this->generateUserInsights($user, $metrics, $events),
        ];
    }

    /**
     * Get system-wide engagement metrics.
     */
    private function getSystemEngagementMetrics(Carbon|CarbonImmutable $startDate, Carbon|CarbonImmutable $endDate): array
    {
        $metrics = UserEngagementMetric::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('
                SUM(login_count) as total_logins,
                SUM(ideas_created) as total_ideas,
                SUM(suggestions_submitted) as total_suggestions,
                SUM(upvotes_given) as total_upvotes_given,
                SUM(upvotes_received) as total_upvotes_received,
                SUM(points_earned) as total_points,
                AVG(engagement_score) as avg_engagement,
                COUNT(DISTINCT user_id) as active_users
            ')
            ->first();

        $events = AnalyticsEvent::whereBetween('occurred_at', [$startDate, $endDate])
            ->selectRaw('event_type, COUNT(*) as count')
            ->groupBy('event_type')
            ->pluck('count', 'event_type');

        $topContributors = UserEngagementMetric::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('user_id, SUM(points_earned) as total_points')
            ->groupBy('user_id')
            ->orderByDesc('total_points')
            ->limit(10)
            ->with('user')
            ->get();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $startDate->diffInDays($endDate) + 1,
            ],
            'overview' => [
                'total_users' => User::count(),
                'active_users' => $metrics->active_users ?? 0,
                'total_ideas' => Idea::count(),
                'total_suggestions' => Suggestion::count(),
                'engagement_rate' => User::count() > 0 ? round(($metrics->active_users ?? 0) / User::count() * 100, 2) : 0,
            ],
            'metrics' => [
                'total_logins' => $metrics->total_logins ?? 0,
                'total_ideas_created' => $metrics->total_ideas ?? 0,
                'total_suggestions' => $metrics->total_suggestions ?? 0,
                'total_upvotes' => ($metrics->total_upvotes_given ?? 0) + ($metrics->total_upvotes_received ?? 0),
                'total_points_awarded' => $metrics->total_points ?? 0,
                'average_engagement_score' => round($metrics->avg_engagement ?? 0, 2),
            ],
            'events_breakdown' => $events,
            'top_contributors' => $topContributors,
            'trends' => $this->getSystemTrends($startDate, $endDate),
        ];
    }

    /**
     * Get idea lifecycle analytics.
     */
    public function getIdeaLifecycleAnalytics(int $days = 30): array
    {
        $endDate = now();
        $startDate = $endDate->copy()->subDays($days);

        $analytics = IdeaLifecycleAnalytic::whereBetween('idea_created_at', [$startDate, $endDate])
            ->with('idea')
            ->get();

        $statusBreakdown = $analytics->groupBy('current_status')
            ->map->count();

        $performanceMetrics = [
            'avg_time_to_first_collaboration' => $analytics->avg('time_to_first_collaboration_hours'),
            'avg_time_to_submission' => $analytics->avg('time_to_submission_hours'),
            'avg_lifecycle_duration' => $analytics->avg('total_lifecycle_days'),
            'avg_collaboration_rate' => $analytics->avg('collaboration_rate'),
            'avg_acceptance_rate' => $analytics->avg('acceptance_rate'),
        ];

        $topPerformingIdeas = $analytics->sortByDesc('acceptance_rate')
            ->take(10);

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            'overview' => [
                'total_ideas' => $analytics->count(),
                'ideas_with_collaboration' => $analytics->where('total_collaborators', '>', 0)->count(),
                'ideas_submitted' => $analytics->whereNotNull('submitted_at')->count(),
                'ideas_approved' => $analytics->whereNotNull('approved_at')->count(),
            ],
            'status_breakdown' => $statusBreakdown,
            'performance_metrics' => $performanceMetrics,
            'top_performing_ideas' => $topPerformingIdeas,
            'lifecycle_stages' => $this->analyzeLifecycleStages($analytics),
        ];
    }

    /**
     * Update user engagement metrics for a specific date.
     */
    public function updateUserEngagementMetrics(User $user, string $date): void
    {
        $date = Carbon::parse($date)->toDateString();

        $metrics = [
            'login_count' => AnalyticsEvent::where('user_id', $user->id)
                ->whereDate('occurred_at', $date)
                ->where('event_type', 'user_login')
                ->count(),

            'ideas_created' => Idea::where('author_id', $user->id)
                ->whereDate('created_at', $date)
                ->count(),

            'suggestions_submitted' => Suggestion::where('author_id', $user->id)
                ->whereDate('created_at', $date)
                ->count(),

            'upvotes_given' => DB::table('idea_upvotes')
                ->where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->count(),

            'upvotes_received' => Idea::where('author_id', $user->id)
                ->withCount(['upvotes' => function ($query) use ($date) {
                    $query->whereDate('created_at', $date);
                }])
                ->get()
                ->sum('upvotes_count'),

            'collaborations_joined' => DB::table('idea_collaborators')
                ->where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->count(),

            'points_earned' => DB::table('points')
                ->where('user_id', $user->id)
                ->whereDate('created_at', $date)
                ->sum('amount') ?? 0,
        ];

        $engagementScore = $this->calculateEngagementScore($metrics);

        UserEngagementMetric::updateOrCreate(
            ['user_id' => $user->id, 'date' => $date],
            array_merge($metrics, ['engagement_score' => $engagementScore])
        );
    }

    /**
     * Update idea lifecycle analytics.
     */
    public function updateIdeaLifecycleAnalytics(Idea $idea): void
    {
        $lifecycleData = [
            'idea_id' => $idea->id,
            'idea_created_at' => $idea->created_at,
            'submitted_at' => $idea->submitted_at,
            'approved_at' => $idea->approved_at,
            'rejected_at' => $idea->rejected_at,
            'first_collaborator_joined_at' => $idea->collaborators()->min('idea_collaborators.created_at'),
            'first_suggestion_at' => $idea->suggestions()->min('created_at'),
            'last_activity_at' => max([
                $idea->updated_at,
                $idea->suggestions()->max('updated_at') ?? $idea->created_at,
                $idea->collaborators()->max('idea_collaborators.created_at') ?? $idea->created_at,
            ]),
            'total_suggestions' => $idea->suggestions()->count(),
            'accepted_suggestions' => $idea->suggestions()->where('status', 'accepted')->count(),
            'rejected_suggestions' => $idea->suggestions()->where('status', 'rejected')->count(),
            'total_upvotes' => $idea->upvotes()->count(),
            'unique_contributors' => $idea->suggestions()->distinct('author_id')->count(),
            'total_collaborators' => $idea->collaborators()->count(),
            'merge_operations' => $idea->merges()->count(),
            'conflict_resolutions' => $idea->merges()->whereNotNull('resolved_at')->count(),
            'current_status' => $idea->status,
        ];

        // Calculate derived metrics
        $lifecycleData['collaboration_rate'] = $lifecycleData['total_suggestions'] > 0
            ? round(($lifecycleData['unique_contributors'] / $lifecycleData['total_suggestions']) * 100, 2)
            : 0;

        $lifecycleData['acceptance_rate'] = $lifecycleData['total_suggestions'] > 0
            ? round(($lifecycleData['accepted_suggestions'] / $lifecycleData['total_suggestions']) * 100, 2)
            : 0;

        $lifecycleData['time_to_first_collaboration_hours'] = $lifecycleData['first_collaborator_joined_at']
            ? Carbon::parse($lifecycleData['created_at'])->diffInHours($lifecycleData['first_collaborator_joined_at'])
            : null;

        $lifecycleData['time_to_submission_hours'] = $lifecycleData['submitted_at']
            ? Carbon::parse($lifecycleData['created_at'])->diffInHours($lifecycleData['submitted_at'])
            : null;

        $endDate = $lifecycleData['approved_at'] ?? $lifecycleData['rejected_at'] ?? $lifecycleData['last_activity_at'] ?? now();
        $lifecycleData['total_lifecycle_days'] = Carbon::parse($lifecycleData['created_at'])->diffInDays($endDate);

        IdeaLifecycleAnalytic::updateOrCreate(
            ['idea_id' => $idea->id],
            $lifecycleData
        );
    }

    /**
     * Calculate engagement score from metrics.
     */
    private function calculateEngagementScore(array $metrics): float
    {
        $score = 0;

        // Login activity (20% weight)
        $score += min($metrics['login_count'] * 2, 20);

        // Content creation (30% weight)
        $score += min(($metrics['ideas_created'] * 5) + ($metrics['suggestions_submitted'] * 2), 30);

        // Social engagement (25% weight)
        $score += min(($metrics['upvotes_given'] * 0.5) + ($metrics['upvotes_received'] * 1) + ($metrics['collaborations_joined'] * 3), 25);

        // Points earned (25% weight)
        $score += min($metrics['points_earned'] * 0.1, 25);

        return round($score, 2);
    }

    /**
     * Build activity timeline from events.
     */
    private function buildActivityTimeline(Collection $events): array
    {
        return $events->groupBy(function ($event) {
            return $event->occurred_at->format('Y-m-d');
        })->map(function ($dayEvents) {
            return $dayEvents->groupBy('event_type')->map->count();
        })->toArray();
    }

    /**
     * Generate user insights.
     */
    private function generateUserInsights(User $user, Collection $metrics, Collection $events): array
    {
        $insights = [];

        $avgEngagement = $metrics->avg('engagement_score');
        if ($avgEngagement > 15) {
            $insights[] = 'High engagement level - great job staying active!';
        } elseif ($avgEngagement < 5) {
            $insights[] = 'Consider increasing your participation to boost your engagement score.';
        }

        $totalIdeas = $metrics->sum('ideas_created');
        $totalSuggestions = $metrics->sum('suggestions_submitted');
        if ($totalIdeas > $totalSuggestions) {
            $insights[] = 'You create more ideas than suggestions - try contributing to others\' ideas!';
        }

        $collaborationRate = $metrics->sum('collaborations_joined');
        if ($collaborationRate === 0) {
            $insights[] = 'Joining collaborations can help you earn more points and network with others.';
        }

        return $insights;
    }

    /**
     * Get system trends over time.
     */
    private function getSystemTrends(Carbon|CarbonImmutable $startDate, Carbon|CarbonImmutable $endDate): array
    {
        $dailyMetrics = UserEngagementMetric::whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('date, SUM(login_count) as logins, SUM(ideas_created) as ideas, SUM(points_earned) as points, AVG(engagement_score) as avg_engagement')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        return [
            'daily_logins' => $dailyMetrics->pluck('logins', 'date'),
            'daily_ideas' => $dailyMetrics->pluck('ideas', 'date'),
            'daily_points' => $dailyMetrics->pluck('points', 'date'),
            'daily_engagement' => $dailyMetrics->pluck('avg_engagement', 'date'),
        ];
    }

    /**
     * Analyze lifecycle stages.
     */
    private function analyzeLifecycleStages(Collection $analytics): array
    {
        $stages = [
            'draft' => $analytics->where('current_status', 'draft'),
            'submitted' => $analytics->where('current_status', 'submitted'),
            'approved' => $analytics->where('current_status', 'approved'),
            'rejected' => $analytics->where('current_status', 'rejected'),
        ];

        return collect($stages)->map(function ($stageAnalytics) {
            return [
                'count' => $stageAnalytics->count(),
                'avg_lifecycle_days' => $stageAnalytics->avg('total_lifecycle_days'),
                'avg_collaboration_rate' => $stageAnalytics->avg('collaboration_rate'),
                'avg_acceptance_rate' => $stageAnalytics->avg('acceptance_rate'),
            ];
        })->toArray();
    }
}
