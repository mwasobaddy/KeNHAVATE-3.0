<?php

namespace App\Services;

use App\Models\Point;
use App\Models\PointsConfiguration;
use App\Models\User;
use Illuminate\Support\Collection;

class PointsService
{
    /**
     * Award points to a user for a specific action.
     */
    public function awardPoints(User $user, string $event, ?User $awardedBy = null, array $metadata = []): Point
    {
        $config = PointsConfiguration::where('event', $event)->first();

        if (! $config) {
            throw new \InvalidArgumentException("No points configuration found for event: {$event}");
        }

        return Point::create([
            'user_id' => $user->id,
            'amount' => $config->points,
            'reason' => $this->getEventDescription($event, $metadata),
            'awarded_by' => $awardedBy?->id,
            'awarded_at' => now(),
        ]);
    }

    /**
     * Get user's total points.
     */
    public function getTotalPoints(User $user): int
    {
        return $user->points()->sum('amount');
    }

    /**
     * Get user's points breakdown by event type.
     */
    public function getPointsBreakdown(User $user): Collection
    {
        return $user->points()
            ->selectRaw('reason, SUM(amount) as total_points, COUNT(*) as count')
            ->groupBy('reason')
            ->orderByDesc('total_points')
            ->get();
    }

    /**
     * Get leaderboard rankings.
     */
    public function getLeaderboard(int $limit = 50, string $period = 'all'): Collection
    {
        $query = User::with(['staff'])
            ->select('users.*')
            ->selectRaw('COALESCE(SUM(points.amount), 0) as total_points')
            ->leftJoin('points', 'users.id', '=', 'points.user_id');

        // Filter by time period
        switch ($period) {
            case 'month':
                $query->where('points.awarded_at', '>=', now()->startOfMonth());
                break;
            case 'week':
                $query->where('points.awarded_at', '>=', now()->startOfWeek());
                break;
            case 'year':
                $query->where('points.awarded_at', '>=', now()->startOfYear());
                break;
        }

        return $query->groupBy('users.id')
            ->orderByDesc('total_points')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'user' => $user,
                    'total_points' => (int) $user->total_points,
                    'rank' => 0, // Will be set below
                    'department' => $user->staff?->department,
                    'designation' => $user->staff?->designation,
                ];
            })
            ->values()
            ->map(function ($item, $index) {
                $item['rank'] = $index + 1;

                return $item;
            });
    }

    /**
     * Get user's rank on the leaderboard.
     */
    public function getUserRank(User $user, string $period = 'all'): array
    {
        $userPoints = $this->getTotalPoints($user);

        $higherRankedCount = User::selectRaw('COUNT(*) as count')
            ->fromRaw('(
                SELECT users.id, COALESCE(SUM(points.amount), 0) as total_points
                FROM users
                LEFT JOIN points ON users.id = points.user_id
                GROUP BY users.id
                HAVING total_points > ?
            ) as ranked_users', [$userPoints])
            ->value('count');

        return [
            'rank' => $higherRankedCount + 1,
            'total_points' => $userPoints,
            'points_to_next_rank' => $this->getPointsToNextRank($userPoints),
        ];
    }

    /**
     * Get points needed to reach next rank milestone.
     */
    protected function getPointsToNextRank(int $currentPoints): int
    {
        $milestones = [100, 250, 500, 1000, 2500, 5000, 10000];

        foreach ($milestones as $milestone) {
            if ($currentPoints < $milestone) {
                return $milestone - $currentPoints;
            }
        }

        return 0; // Already at highest milestone
    }

    /**
     * Get user's achievements/badges.
     */
    public function getUserAchievements(User $user): array
    {
        $totalPoints = $this->getTotalPoints($user);
        $achievements = [];

        // Points-based achievements
        if ($totalPoints >= 100) {
            $achievements[] = ['name' => 'Contributor', 'icon' => 'star', 'description' => 'Earned 100+ points'];
        }
        if ($totalPoints >= 500) {
            $achievements[] = ['name' => 'Active Contributor', 'icon' => 'trophy', 'description' => 'Earned 500+ points'];
        }
        if ($totalPoints >= 1000) {
            $achievements[] = ['name' => 'Super Contributor', 'icon' => 'award', 'description' => 'Earned 1000+ points'];
        }
        if ($totalPoints >= 5000) {
            $achievements[] = ['name' => 'Innovation Champion', 'icon' => 'crown', 'description' => 'Earned 5000+ points'];
        }

        // Activity-based achievements
        $ideaCount = $user->ideas()->count();
        if ($ideaCount >= 1) {
            $achievements[] = ['name' => 'Idea Creator', 'icon' => 'lightbulb', 'description' => 'Submitted first idea'];
        }
        if ($ideaCount >= 5) {
            $achievements[] = ['name' => 'Prolific Creator', 'icon' => 'zap', 'description' => 'Submitted 5+ ideas'];
        }
        if ($ideaCount >= 10) {
            $achievements[] = ['name' => 'Idea Machine', 'icon' => 'rocket', 'description' => 'Submitted 10+ ideas'];
        }

        $suggestionCount = $user->suggestions()->count();
        if ($suggestionCount >= 1) {
            $achievements[] = ['name' => 'Collaborator', 'icon' => 'users', 'description' => 'Made first suggestion'];
        }
        if ($suggestionCount >= 10) {
            $achievements[] = ['name' => 'Team Player', 'icon' => 'handshake', 'description' => 'Made 10+ suggestions'];
        }
        if ($suggestionCount >= 25) {
            $achievements[] = ['name' => 'Collaboration Expert', 'icon' => 'network', 'description' => 'Made 25+ suggestions'];
        }

        $upvoteCount = $user->upvotes()->count();
        if ($upvoteCount >= 10) {
            $achievements[] = ['name' => 'Supporter', 'icon' => 'thumbs-up', 'description' => 'Upvoted 10+ ideas'];
        }
        if ($upvoteCount >= 50) {
            $achievements[] = ['name' => 'Community Supporter', 'icon' => 'heart', 'description' => 'Upvoted 50+ ideas'];
        }

        return $achievements;
    }

    /**
     * Get points configuration for all events.
     */
    public function getPointsConfiguration(): Collection
    {
        return PointsConfiguration::orderBy('event')->get();
    }

    /**
     * Update points configuration for an event.
     */
    public function updatePointsConfiguration(string $event, int $points, User $updatedBy): PointsConfiguration
    {
        return PointsConfiguration::updateOrCreate(
            ['event' => $event],
            [
                'points' => $points,
                'set_by' => $updatedBy->id,
            ]
        );
    }

    /**
     * Get user's points history.
     */
    public function getPointsHistory(User $user, int $limit = 20): Collection
    {
        return $user->points()
            ->with('awardedBy')
            ->orderByDesc('awarded_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get points statistics.
     */
    public function getPointsStatistics(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::whereHas('points')->count();
        $totalPoints = Point::sum('amount');
        $averagePoints = $totalUsers > 0 ? round($totalPoints / $totalUsers, 2) : 0;

        $topEvents = Point::selectRaw('reason, SUM(amount) as total_points, COUNT(*) as count')
            ->groupBy('reason')
            ->orderByDesc('total_points')
            ->limit(5)
            ->get();

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'total_points' => $totalPoints,
            'average_points' => $averagePoints,
            'top_events' => $topEvents,
        ];
    }

    /**
     * Get event description for points.
     */
    protected function getEventDescription(string $event, array $metadata = []): string
    {
        return match ($event) {
            'idea_created' => 'Created a new idea',
            'idea_submitted' => 'Submitted idea for review',
            'idea_approved' => 'Idea was approved',
            'idea_implemented' => 'Idea was implemented',
            'suggestion_created' => 'Made a suggestion on an idea',
            'suggestion_accepted' => 'Suggestion was accepted',
            'idea_upvoted' => 'Received an upvote on idea',
            'collaboration_joined' => 'Joined idea collaboration',
            'merge_performed' => 'Successfully merged suggestions',
            'conflict_resolved' => 'Resolved a merge conflict',
            default => ucfirst(str_replace('_', ' ', $event)),
        };
    }

    /**
     * Award points for specific events (called by other services).
     */
    public function awardIdeaCreated(User $user): void
    {
        $this->awardPoints($user, 'idea_created');
    }

    public function awardIdeaSubmitted(User $user): void
    {
        $this->awardPoints($user, 'idea_submitted');
    }

    public function awardIdeaApproved(User $user): void
    {
        $this->awardPoints($user, 'idea_approved');
    }

    public function awardIdeaImplemented(User $user): void
    {
        $this->awardPoints($user, 'idea_implemented');
    }

    public function awardSuggestionCreated(User $user): void
    {
        $this->awardPoints($user, 'suggestion_created');
    }

    public function awardSuggestionAccepted(User $user): void
    {
        $this->awardPoints($user, 'suggestion_accepted');
    }

    public function awardIdeaUpvoted(User $user): void
    {
        $this->awardPoints($user, 'idea_upvoted');
    }

    public function awardCollaborationJoined(User $user): void
    {
        $this->awardPoints($user, 'collaboration_joined');
    }

    public function awardMergePerformed(User $user): void
    {
        $this->awardPoints($user, 'merge_performed');
    }

    public function awardConflictResolved(User $user): void
    {
        $this->awardPoints($user, 'conflict_resolved');
    }
}
