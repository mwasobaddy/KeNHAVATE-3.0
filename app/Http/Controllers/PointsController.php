<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PointsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    public function __construct(
        private PointsService $pointsService
    ) {}

    /**
     * Get leaderboard rankings.
     */
    public function leaderboard(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
            'period' => 'sometimes|in:all,week,month,year',
        ]);

        $limit = $request->input('limit', 50);
        $period = $request->input('period', 'all');

        $leaderboard = $this->pointsService->getLeaderboard($limit, $period);

        return response()->json([
            'leaderboard' => $leaderboard,
            'period' => $period,
            'total_count' => $leaderboard->count(),
        ]);
    }

    /**
     * Get current user's points statistics.
     */
    public function userStats(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = [
            'total_points' => $this->pointsService->getTotalPoints($user),
            'rank' => $this->pointsService->getUserRank($user),
            'achievements' => $this->pointsService->getUserAchievements($user),
            'points_breakdown' => $this->pointsService->getPointsBreakdown($user),
            'points_history' => $this->pointsService->getPointsHistory($user, 10),
        ];

        return response()->json($stats);
    }

    /**
     * Get specific user's public profile (points, achievements, etc.).
     */
    public function userProfile(User $user): JsonResponse
    {
        // Only show public information
        $profile = [
            'id' => $user->id,
            'name' => $user->name,
            'total_points' => $this->pointsService->getTotalPoints($user),
            'rank' => $this->pointsService->getUserRank($user),
            'achievements' => $this->pointsService->getUserAchievements($user),
            'stats' => [
                'ideas_count' => $user->ideas()->count(),
                'suggestions_count' => $user->suggestions()->count(),
                'upvotes_received' => $user->upvotes()->count(),
                'collaborations_count' => $user->ideaCollaborations()->count(),
            ],
        ];

        return response()->json($profile);
    }

    /**
     * Get points configuration (admin only).
     */
    public function configuration(): JsonResponse
    {
        $this->authorize('manage', \App\Models\PointsConfiguration::class);

        $config = $this->pointsService->getPointsConfiguration();

        return response()->json([
            'configuration' => $config,
        ]);
    }

    /**
     * Update points configuration (admin only).
     */
    public function updateConfiguration(Request $request): JsonResponse
    {
        $this->authorize('manage', \App\Models\PointsConfiguration::class);

        $request->validate([
            'event' => 'required|string',
            'points' => 'required|integer|min:0|max:1000',
        ]);

        $config = $this->pointsService->updatePointsConfiguration(
            $request->input('event'),
            $request->input('points'),
            $request->user()
        );

        return response()->json([
            'message' => 'Points configuration updated successfully',
            'configuration' => $config,
        ]);
    }

    /**
     * Get points statistics for admin dashboard.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Point::class);

        $stats = $this->pointsService->getPointsStatistics();

        return response()->json($stats);
    }

    /**
     * Get user's detailed points history.
     */
    public function pointsHistory(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'sometimes|integer|min:1|max:100',
        ]);

        $limit = $request->input('limit', 50);
        $history = $this->pointsService->getPointsHistory($request->user(), $limit);

        return response()->json([
            'history' => $history,
            'total_count' => $history->count(),
        ]);
    }

    /**
     * Get achievements for current user.
     */
    public function achievements(): JsonResponse
    {
        $achievements = $this->pointsService->getUserAchievements($request->user());

        return response()->json([
            'achievements' => $achievements,
            'total_count' => count($achievements),
        ]);
    }

    /**
     * Get top performers for a specific metric.
     */
    public function topPerformers(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|in:ideas,suggestions,upvotes,collaborations,points',
            'limit' => 'sometimes|integer|min:1|max:50',
        ]);

        $metric = $request->input('metric');
        $limit = $request->input('limit', 10);

        $query = User::with(['staff']);

        switch ($metric) {
            case 'ideas':
                $query->withCount('ideas')->orderByDesc('ideas_count');
                break;
            case 'suggestions':
                $query->withCount('suggestions')->orderByDesc('suggestions_count');
                break;
            case 'upvotes':
                $query->selectRaw('users.*, COUNT(idea_upvotes.id) as upvotes_count')
                    ->leftJoin('ideas', 'users.id', '=', 'ideas.author_id')
                    ->leftJoin('idea_upvotes', 'ideas.id', '=', 'idea_upvotes.idea_id')
                    ->groupBy('users.id')
                    ->orderByDesc('upvotes_count');
                break;
            case 'collaborations':
                $query->withCount('ideaCollaborations')->orderByDesc('idea_collaborations_count');
                break;
            case 'points':
                $query->selectRaw('users.*, COALESCE(SUM(points.amount), 0) as total_points')
                    ->leftJoin('points', 'users.id', '=', 'points.user_id')
                    ->groupBy('users.id')
                    ->orderByDesc('total_points');
                break;
        }

        $performers = $query->limit($limit)->get()->map(function ($user, $index) use ($metric) {
            $value = match ($metric) {
                'ideas' => $user->ideas_count,
                'suggestions' => $user->suggestions_count,
                'upvotes' => $user->upvotes_count ?? 0,
                'collaborations' => $user->idea_collaborations_count,
                'points' => $user->total_points ?? 0,
            };

            return [
                'rank' => $index + 1,
                'user' => $user,
                'value' => $value,
                'metric' => $metric,
            ];
        });

        return response()->json([
            'performers' => $performers,
            'metric' => $metric,
        ]);
    }
}
