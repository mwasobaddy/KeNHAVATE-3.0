<?php

namespace App\Services;

use App\Jobs\SendNotificationEmails;
use App\Mail\NotificationMail;
use App\Models\Idea;
use App\Models\Notification;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Create a notification for a user.
     */
    public function createNotification(
        User $user,
        string $type,
        string $title,
        string $message,
        ?User $sender = null,
        $notifiable = null,
        array $data = []
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'sender_id' => $sender?->id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'notifiable_id' => $notifiable?->id,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
        ]);
    }

    /**
     * Notify idea collaborators about a new suggestion.
     */
    public function notifySuggestionCreated(Suggestion $suggestion): void
    {
        $idea = $suggestion->idea;
        $author = $suggestion->author;

        if (! $author instanceof User || ! $idea instanceof Idea) {
            return;
        }

        // Notify idea author
        if ($idea->author && $idea->author_id !== $author->id) {
            $this->createNotification(
                $idea->author,
                'suggestion_created',
                'New Suggestion on Your Idea',
                "{$author->name} suggested: \"{$suggestion->content}\"",
                $author,
                $idea,
                ['suggestion_id' => $suggestion->id]
            );
        }

        // Notify other collaborators
        foreach ($idea->collaborators as $collaborator) {
            if (! $collaborator->user instanceof User) {
                continue;
            }

            if ($collaborator->user_id !== $author->id && $collaborator->user_id !== $idea->author_id) {
                $this->createNotification(
                    $collaborator->user,
                    'suggestion_created',
                    'New Suggestion on Collaborated Idea',
                    "{$author->name} suggested on \"{$idea->title}\": \"{$suggestion->content}\"",
                    $author,
                    $idea,
                    ['suggestion_id' => $suggestion->id]
                );
            }
        }
    }

    /**
     * Notify suggestion author about acceptance.
     */
    public function notifySuggestionAccepted(Suggestion $suggestion): void
    {
        $idea = $suggestion->idea;
        $acceptedBy = $suggestion->acceptedBy;

        $this->createNotification(
            $suggestion->author,
            'suggestion_accepted',
            'Your Suggestion Was Accepted!',
            "Your suggestion on \"{$idea->title}\" was accepted by {$acceptedBy->name}",
            $acceptedBy,
            $idea,
            ['suggestion_id' => $suggestion->id]
        );
    }

    /**
     * Notify idea author about upvotes.
     */
    public function notifyIdeaUpvoted(Idea $idea, User $voter): void
    {
        // Only notify if the voter is not the author
        if ($idea->author_id !== $voter->id) {
            $this->createNotification(
                $idea->author,
                'idea_upvoted',
                'Your Idea Got an Upvote!',
                "{$voter->name} upvoted your idea \"{$idea->title}\"",
                $voter,
                $idea
            );
        }
    }

    /**
     * Notify idea author about new collaborators.
     */
    public function notifyCollaboratorJoined(Idea $idea, User $collaborator): void
    {
        // Only notify if the collaborator is not the author
        if ($idea->author_id !== $collaborator->id) {
            $this->createNotification(
                $idea->author,
                'collaborator_joined',
                'New Collaborator Joined!',
                "{$collaborator->name} joined your idea \"{$idea->title}\" as a collaborator",
                $collaborator,
                $idea
            );
        }
    }

    /**
     * Send email notifications for unread notifications.
     */
    public function sendEmailNotifications(User $user): void
    {
        $unreadNotifications = $user->notifications()
            ->where('is_read', false)
            ->where('is_email_sent', false)
            ->where('created_at', '>=', now()->subHours(24)) // Only recent notifications
            ->get();

        if ($unreadNotifications->isEmpty()) {
            return;
        }

        // Group notifications by type for better email formatting
        $groupedNotifications = $unreadNotifications->groupBy('type');

        try {
            Mail::to($user)->send(new NotificationMail($groupedNotifications));

            // Mark emails as sent
            foreach ($unreadNotifications as $notification) {
                $notification->markEmailAsSent();
            }
        } catch (\Exception $e) {
            // Log error but don't fail the process
            \Log::error('Failed to send notification email', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Queue email notifications for a user.
     */
    public function queueEmailNotifications(User $user): void
    {
        SendNotificationEmails::dispatch($user)->onQueue('emails');
    }

    /**
     * Get user's unread notification count.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->notifications()->unread()->count();
    }

    /**
     * Mark all user's notifications as read.
     */
    public function markAllAsRead(User $user): void
    {
        $user->notifications()
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }
}
