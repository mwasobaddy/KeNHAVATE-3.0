<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Get user engagement dashboard data.
     */
    public function userDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        $days = $request->input('days', 30);
        $user = $request->user();

        $data = $this->analyticsService->getUserEngagementDashboard($user, $days);

        return response()->json($data);
    }

    /**
     * Get system-wide analytics dashboard.
     */
    public function systemDashboard(Request $request): JsonResponse
    {
        $this->authorize('viewSystemAnalytics', User::class);

        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        $days = $request->input('days', 30);

        $data = $this->analyticsService->getUserEngagementDashboard(null, $days);

        return response()->json($data);
    }

    /**
     * Get idea lifecycle analytics.
     */
    public function ideaLifecycle(Request $request): JsonResponse
    {
        $this->authorize('viewSystemAnalytics', User::class);

        $request->validate([
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        $days = $request->input('days', 30);

        $data = $this->analyticsService->getIdeaLifecycleAnalytics($days);

        return response()->json($data);
    }

    /**
     * Get user comparison analytics.
     */
    public function userComparison(Request $request): JsonResponse
    {
        $this->authorize('viewUserComparison', User::class);

        $request->validate([
            'user_ids' => 'required|array|min:1|max:10',
            'user_ids.*' => 'integer|exists:users,id',
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        $userIds = $request->input('user_ids');
        $days = $request->input('days', 30);

        $users = User::whereIn('id', $userIds)->get();
        $comparisonData = [];

        foreach ($users as $user) {
            $comparisonData[] = $this->analyticsService->getUserEngagementDashboard($user, $days);
        }

        return response()->json([
            'comparison' => $comparisonData,
            'period_days' => $days,
        ]);
    }

    /**
     * Get analytics export data.
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('exportAnalytics', User::class);

        $request->validate([
            'type' => 'required|in:user_engagement,idea_lifecycle,system_overview',
            'format' => 'required|in:json,csv',
            'days' => 'sometimes|integer|min:1|max:365',
            'user_id' => 'sometimes|integer|exists:users,id',
        ]);

        $type = $request->input('type');
        $format = $request->input('format');
        $days = $request->input('days', 30);

        $data = match ($type) {
            'user_engagement' => $this->analyticsService->getUserEngagementDashboard(
                $request->has('user_id') ? User::find($request->user_id) : null,
                $days
            ),
            'idea_lifecycle' => $this->analyticsService->getIdeaLifecycleAnalytics($days),
            'system_overview' => $this->analyticsService->getUserEngagementDashboard(null, $days),
        };

        if ($format === 'csv') {
            // Convert to CSV format (simplified example)
            $data = $this->convertToCsv($data);
        }

        return response()->json([
            'data' => $data,
            'format' => $format,
            'exported_at' => now(),
        ]);
    }

    /**
     * Get real-time analytics metrics.
     */
    public function realtime(Request $request): JsonResponse
    {
        $this->authorize('viewSystemAnalytics', User::class);

        $request->validate([
            'metrics' => 'sometimes|array',
            'metrics.*' => 'string|in:active_users,today_logins,today_ideas,today_suggestions',
        ]);

        $metrics = $request->input('metrics', ['active_users', 'today_logins', 'today_ideas', 'today_suggestions']);

        $data = [];

        if (in_array('active_users', $metrics)) {
            $data['active_users'] = User::where('last_login_at', '>', now()->subMinutes(30))->count();
        }

        if (in_array('today_logins', $metrics)) {
            $data['today_logins'] = \App\Models\AnalyticsEvent::where('event_type', 'user_login')
                ->whereDate('occurred_at', today())
                ->count();
        }

        if (in_array('today_ideas', $metrics)) {
            $data['today_ideas'] = \App\Models\Idea::whereDate('created_at', today())->count();
        }

        if (in_array('today_suggestions', $metrics)) {
            $data['today_suggestions'] = \App\Models\Suggestion::whereDate('created_at', today())->count();
        }

        return response()->json([
            'metrics' => $data,
            'timestamp' => now(),
        ]);
    }

    /**
     * Get analytics insights and recommendations.
     */
    public function insights(Request $request): JsonResponse
    {
        $this->authorize('viewSystemAnalytics', User::class);

        $request->validate([
            'type' => 'required|in:system,user',
            'user_id' => 'required_if:type,user|integer|exists:users,id',
            'days' => 'sometimes|integer|min:1|max:365',
        ]);

        $type = $request->input('type');
        $days = $request->input('days', 30);

        if ($type === 'user') {
            $user = User::find($request->user_id);
            $data = $this->analyticsService->getUserEngagementDashboard($user, $days);
            $insights = $data['insights'] ?? [];
        } else {
            $data = $this->analyticsService->getUserEngagementDashboard(null, $days);
            $insights = $this->generateSystemInsights($data);
        }

        return response()->json([
            'insights' => $insights,
            'data' => $data,
            'generated_at' => now(),
        ]);
    }

    /**
     * Convert data to CSV format (simplified).
     */
    private function convertToCsv(array $data): string
    {
        // This is a simplified CSV conversion
        // In a real application, you'd use a proper CSV library
        $csv = '';

        if (isset($data['metrics'])) {
            $csv .= "Metric,Value\n";
            foreach ($data['metrics'] as $key => $value) {
                $csv .= "\"{$key}\",\"{$value}\"\n";
            }
        }

        return $csv;
    }

    /**
     * Generate system-level insights.
     */
    private function generateSystemInsights(array $data): array
    {
        $insights = [];

        $engagementRate = $data['overview']['engagement_rate'] ?? 0;
        if ($engagementRate < 50) {
            $insights[] = 'Low user engagement rate. Consider implementing more gamification features.';
        } elseif ($engagementRate > 80) {
            $insights[] = 'Excellent engagement rate! Keep up the good work.';
        }

        $totalIdeas = $data['overview']['total_ideas'] ?? 0;
        $activeUsers = $data['overview']['active_users'] ?? 0;
        if ($activeUsers > 0 && $totalIdeas / $activeUsers < 1) {
            $insights[] = 'Users are creating fewer ideas than expected. Consider idea generation campaigns.';
        }

        $avgEngagement = $data['metrics']['average_engagement_score'] ?? 0;
        if ($avgEngagement < 10) {
            $insights[] = 'Average engagement score is low. Review user onboarding and feature adoption.';
        }

        return $insights;
    }
}
