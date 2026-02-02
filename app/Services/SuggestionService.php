<?php

namespace App\Services;

use App\Events\SuggestionAccepted;
use App\Events\SuggestionCreated;
use App\Models\Idea;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SuggestionService
{
    public function __construct(
        private NotificationService $notificationService,
        private PointsService $pointsService
    ) {}

    public function createSuggestion(Idea $idea, array $data, User $author, ?Suggestion $parent = null): Suggestion
    {
        $suggestion = Suggestion::create([
            'idea_id' => $idea->id,
            'author_id' => $author->id,
            'parent_id' => $parent?->id,
            'content' => $data['content'],
            'type' => $data['type'] ?? 'general',
        ]);

        // Fire the real-time event
        SuggestionCreated::dispatch($suggestion);

        // Award points for creating suggestion
        $this->pointsService->awardSuggestionCreated($author);

        // Send notifications
        $this->notificationService->notifySuggestionCreated($suggestion);

        // Queue email notifications for relevant users
        $idea = $suggestion->idea;
        if ($idea->author && $idea->author_id !== $suggestion->author_id) {
            $this->notificationService->queueEmailNotifications($idea->author);
        }
        foreach ($idea->collaborators as $collaborator) {
            if (! $collaborator->user) {
                continue;
            }

            if ($collaborator->user_id !== $suggestion->author_id) {
                $this->notificationService->queueEmailNotifications($collaborator->user);
            }
        }

        return $suggestion;
    }

    public function acceptSuggestion(Suggestion $suggestion, User $user): Suggestion
    {
        $suggestion->accept($user);

        // Fire the real-time event
        SuggestionAccepted::dispatch($suggestion);

        // Award points for accepted suggestion
        $this->pointsService->awardSuggestionAccepted($suggestion->author);

        // Send notifications
        $this->notificationService->notifySuggestionAccepted($suggestion);

        // Queue email notification for the suggestion author
        $this->notificationService->queueEmailNotifications($suggestion->author);

        return $suggestion;
    }

    public function rejectSuggestion(Suggestion $suggestion): Suggestion
    {
        $suggestion->reject();

        return $suggestion;
    }

    public function getSuggestionsForIdea(Idea $idea, array $filters = []): Collection
    {
        $query = $idea->suggestions()->with(['author', 'replies', 'acceptedBy']);

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'accepted':
                    $query->accepted();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
                case 'pending':
                    $query->pending();
                    break;
            }
        }

        return $query->topLevel()->orderBy('created_at', 'desc')->get();
    }

    public function getPaginatedSuggestionsForIdea(Idea $idea, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $idea->suggestions()->with(['author', 'replies.author', 'acceptedBy']);

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'accepted':
                    $query->accepted();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
                case 'pending':
                    $query->pending();
                    break;
            }
        }

        return $query->topLevel()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUserSuggestions(User $user, array $filters = []): Collection
    {
        $query = $user->suggestions()->with(['idea', 'replies']);

        if (isset($filters['idea_id'])) {
            $query->where('idea_id', $filters['idea_id']);
        }

        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['status'])) {
            switch ($filters['status']) {
                case 'accepted':
                    $query->accepted();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
                case 'pending':
                    $query->pending();
                    break;
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getSuggestionThread(Suggestion $suggestion): Collection
    {
        // Get the root suggestion if this is a reply
        $rootSuggestion = $suggestion->isTopLevel() ? $suggestion : $this->findRootSuggestion($suggestion);

        return $this->buildThread($rootSuggestion);
    }

    public function getSuggestionStats(Idea $idea): array
    {
        $suggestions = $idea->suggestions();

        return [
            'total_suggestions' => $suggestions->count(),
            'accepted_suggestions' => $suggestions->accepted()->count(),
            'rejected_suggestions' => $suggestions->rejected()->count(),
            'pending_suggestions' => $suggestions->pending()->count(),
            'suggestions_by_type' => [
                'improvement' => $suggestions->byType('improvement')->count(),
                'question' => $suggestions->byType('question')->count(),
                'concern' => $suggestions->byType('concern')->count(),
                'support' => $suggestions->byType('support')->count(),
                'general' => $suggestions->byType('general')->count(),
            ],
        ];
    }

    protected function findRootSuggestion(Suggestion $suggestion): Suggestion
    {
        $current = $suggestion;

        while ($current->parent) {
            $current = $current->parent;
        }

        return $current;
    }

    protected function buildThread(Suggestion $rootSuggestion): Collection
    {
        $thread = collect([$rootSuggestion]);

        $this->addRepliesToThread($thread, $rootSuggestion);

        return $thread;
    }

    protected function addRepliesToThread(Collection &$thread, Suggestion $suggestion): void
    {
        foreach ($suggestion->replies()->with(['author', 'acceptedBy'])->orderBy('created_at')->get() as $reply) {
            $thread->push($reply);
            $this->addRepliesToThread($thread, $reply);
        }
    }
}
