<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Onboarding\StaffOnboardingController;
use App\Http\Controllers\Onboarding\UserOnboardingController;
use App\Services\PointsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/login');
})->name('home');

Route::get('login', [OtpController::class, 'showLoginForm'])->name('login');
Route::post('login', [OtpController::class, 'sendOtp'])->name('login.send');
Route::post('otp/verify', [OtpController::class, 'verifyOtp'])->name('otp.verify');
Route::post('otp/resend', [OtpController::class, 'resendOtp'])->name('otp.resend');

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('onboarding/user', [UserOnboardingController::class, 'show'])->name('user.onboarding');
    Route::post('onboarding/user', [UserOnboardingController::class, 'store'])->name('user.onboarding.store');

    Route::get('onboarding/staff', [StaffOnboardingController::class, 'show'])->name('staff.onboarding');
    Route::post('onboarding/staff', [StaffOnboardingController::class, 'store'])->name('staff.onboarding.store');
});

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Ideas Routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('ideas', App\Http\Controllers\IdeaController::class);

    // Idea-specific actions
    Route::post('ideas/{idea}/submit', [App\Http\Controllers\IdeaController::class, 'submit'])->name('ideas.submit');
    Route::post('ideas/{idea}/collaboration/enable', [App\Http\Controllers\IdeaController::class, 'enableCollaboration'])->name('ideas.enable-collaboration');
    Route::post('ideas/{idea}/collaboration/disable', [App\Http\Controllers\IdeaController::class, 'disableCollaboration'])->name('ideas.disable-collaboration');
    Route::post('ideas/{idea}/upvote', [App\Http\Controllers\IdeaController::class, 'upvote'])->name('ideas.upvote');
    Route::delete('ideas/{idea}/upvote', [App\Http\Controllers\IdeaController::class, 'removeUpvote'])->name('ideas.remove-upvote');
    Route::post('ideas/{idea}/join-collaboration', [App\Http\Controllers\IdeaController::class, 'addCollaborator'])->name('ideas.join-collaboration');
    Route::delete('ideas/{idea}/collaborators/{user}', [App\Http\Controllers\IdeaController::class, 'removeCollaborator'])->name('ideas.remove-collaborator');

    // Suggestions Routes
    Route::get('ideas/{idea}/suggestions', [App\Http\Controllers\SuggestionController::class, 'index'])->name('ideas.suggestions.index');
    Route::post('ideas/{idea}/suggestions', [App\Http\Controllers\SuggestionController::class, 'store'])->name('ideas.suggestions.store');
    Route::get('ideas/{idea}/suggestions/{suggestion}', [App\Http\Controllers\SuggestionController::class, 'show'])->name('ideas.suggestions.show');
    Route::post('ideas/{idea}/suggestions/{suggestion}/accept', [App\Http\Controllers\SuggestionController::class, 'accept'])->name('ideas.suggestions.accept');
    Route::post('ideas/{idea}/suggestions/{suggestion}/reject', [App\Http\Controllers\SuggestionController::class, 'reject'])->name('ideas.suggestions.reject');

    // Notifications Routes
    Route::get('notifications', [App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread-count', [App\Http\Controllers\NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::patch('notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.mark-read');
    Route::patch('notifications/mark-all-read', [App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::delete('notifications/{id}', [App\Http\Controllers\NotificationController::class, 'destroy'])->name('notifications.destroy');
    // Merge Routes
    Route::get('ideas/{idea}/merge/analyze', [App\Http\Controllers\MergeController::class, 'analyzeConflicts'])->name('ideas.merge.analyze');
    Route::post('ideas/{idea}/merge', [App\Http\Controllers\MergeController::class, 'mergeSuggestions'])->name('ideas.merge.suggestions');
    Route::get('ideas/{idea}/merge/history', [App\Http\Controllers\MergeController::class, 'getMergeHistory'])->name('ideas.merge.history');
    Route::get('ideas/{idea}/merge/conflicts', [App\Http\Controllers\MergeController::class, 'getUnresolvedConflicts'])->name('ideas.merge.conflicts');
    Route::post('merge/conflicts/{conflict}/resolve', [App\Http\Controllers\MergeController::class, 'resolveConflict'])->name('merge.conflicts.resolve');
    Route::get('merge/conflicts/{conflict}/resolve', [App\Http\Controllers\MergeController::class, 'resolveConflict'])->name('merge.conflicts.resolve');
    Route::get('ideas/{idea}/merge/suggestions', [App\Http\Controllers\MergeController::class, 'getMergeSuggestions'])->name('ideas.merge.suggestions');

    // Points and Leaderboard Routes
    Route::get('leaderboard', function (Request $request) {
        $limit = $request->input('limit', 50);
        $period = $request->input('period', 'all');

        $pointsService = app(PointsService::class);
        $leaderboard = $pointsService->getLeaderboard($limit, $period);

        return Inertia::render('leaderboard/index', [
            'leaderboard' => $leaderboard,
            'period' => $period,
            'totalCount' => $leaderboard->count(),
        ]);
    })->name('leaderboard');
    Route::get('points/history', [App\Http\Controllers\PointsController::class, 'pointsHistory'])->name('points.history');
    Route::get('points/achievements', [App\Http\Controllers\PointsController::class, 'achievements'])->name('points.achievements');
    Route::get('users/{user}/profile', [App\Http\Controllers\PointsController::class, 'userProfile'])->name('users.profile');
    Route::get('points/top-performers', [App\Http\Controllers\PointsController::class, 'topPerformers'])->name('points.top-performers');

    // Admin Points Routes
    Route::middleware('can:manage,App\\Models\\PointsConfiguration')->group(function () {
        Route::get('admin/points/configuration', [App\Http\Controllers\PointsController::class, 'configuration'])->name('admin.points.configuration');
        Route::put('admin/points/configuration', [App\Http\Controllers\PointsController::class, 'updateConfiguration'])->name('admin.points.configuration.update');
        Route::get('admin/points/statistics', [App\Http\Controllers\PointsController::class, 'statistics'])->name('admin.points.statistics');
    });

    // Analytics Routes
    Route::get('analytics', function () {
        return Inertia::render('analytics/dashboard');
    })->name('analytics.index');
    Route::get('analytics/dashboard', [App\Http\Controllers\AnalyticsController::class, 'userDashboard'])->name('analytics.dashboard');
    Route::get('analytics/realtime', [App\Http\Controllers\AnalyticsController::class, 'realtime'])->name('analytics.realtime');

    // Admin Analytics Routes
    Route::middleware('can:viewAnalytics,App\\Models\\User')->group(function () {
        Route::get('admin/analytics', function () {
            return Inertia::render('admin/analytics/index');
        })->name('admin.analytics.index');
        Route::get('admin/analytics/system', [App\Http\Controllers\AnalyticsController::class, 'systemDashboard'])->name('admin.analytics.system');
        Route::get('admin/analytics/ideas', [App\Http\Controllers\AnalyticsController::class, 'ideaLifecycle'])->name('admin.analytics.ideas');
        Route::post('admin/analytics/comparison', [App\Http\Controllers\AnalyticsController::class, 'userComparison'])->name('admin.analytics.comparison');
        Route::get('admin/analytics/export', [App\Http\Controllers\AnalyticsController::class, 'export'])->name('admin.analytics.export');
        Route::get('admin/analytics/insights', [App\Http\Controllers\AnalyticsController::class, 'insights'])->name('admin.analytics.insights');
    });
});

require __DIR__.'/settings.php';
