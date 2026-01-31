<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Display user's notifications.
     */
    public function index(Request $request): InertiaResponse
    {
        $user = $request->user();
        $perPage = 20;

        $query = $user->notifications()
            ->with(['sender', 'notifiable'])
            ->orderBy('created_at', 'desc');

        // Filter by read status
        if ($request->has('read')) {
            $isRead = $request->boolean('read');
            $query->where('is_read', $isRead);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $notifications = $query->paginate($perPage);

        return Inertia::render('notifications/index', [
            'notifications' => $notifications,
            'unreadCount' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Get unread notification count (API endpoint).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->notificationService->getUnreadCount($request->user()),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notificationService->markAllAsRead($request->user());

        return response()->json(['success' => true]);
    }

    /**
     * Delete a notification.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Get notification preferences (for future implementation).
     */
    public function preferences(Request $request): InertiaResponse
    {
        return Inertia::render('Notifications/Preferences', [
            'preferences' => [
                'email_notifications' => true,
                'push_notifications' => true,
                'suggestion_notifications' => true,
                'upvote_notifications' => true,
                'collaborator_notifications' => true,
            ],
        ]);
    }
}
