<?php

namespace App\Services;

use App\Events\CollaboratorJoined;
use App\Events\IdeaUpvoted;
use App\Models\Idea;
use App\Models\IdeaRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class IdeaService
{
    public function __construct(
        private NotificationService $notificationService,
        private PointsService $pointsService
    ) {}

    public function createIdea(array $data, User $author): Idea
    {
        return DB::transaction(function () use ($data, $author) {
            $idea = Idea::create([
                'author_id' => $author->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'problem_statement' => $data['problem_statement'],
                'proposed_solution' => $data['proposed_solution'],
                'cost_benefit_analysis' => $data['cost_benefit_analysis'] ?? null,
                'proposal_document_path' => $data['proposal_document_path'] ?? null,
                'collaboration_enabled' => $data['collaboration_enabled'] ?? false,
                'status' => 'draft',
            ]);

            // Create initial revision
            $this->createRevision($idea, $author, 1, 'Initial creation', $data, []);

            return $idea;
        });
    }

    public function updateIdea(Idea $idea, array $data, User $user): Idea
    {
        return DB::transaction(function () use ($idea, $data, $user) {
            $oldData = $idea->only([
                'title', 'description', 'category_id', 'problem_statement',
                'proposed_solution', 'cost_benefit_analysis', 'proposal_document_path',
            ]);

            $idea->update($data);

            $newData = $idea->only([
                'title', 'description', 'category_id', 'problem_statement',
                'proposed_solution', 'cost_benefit_analysis', 'proposal_document_path',
            ]);

            $changes = $this->calculateChanges($oldData, $newData);

            if (! empty($changes)) {
                $revisionNumber = $idea->revisions()->max('revision_number') + 1;
                $this->createRevision($idea, $user, $revisionNumber, 'Updated idea', $newData, $oldData, $changes);
            }

            return $idea;
        });
    }

    public function approveIdea(Idea $idea): Idea
    {
        $idea->approve();

        return $idea;
    }

    public function rejectIdea(Idea $idea): Idea
    {
        $idea->reject();

        return $idea;
    }

    public function submitIdea(Idea $idea): Idea
    {
        $idea->submit();

        // Award points for submitting idea
        $this->pointsService->awardIdeaSubmitted($idea->author);

        return $idea;
    }

    public function enableCollaboration(Idea $idea): Idea
    {
        $idea->enableCollaboration();

        return $idea;
    }

    public function disableCollaboration(Idea $idea): Idea
    {
        $idea->disableCollaboration();

        return $idea;
    }

    public function addCollaborator(Idea $idea, User $user): void
    {
        if (! $idea->isUserCollaborator($user)) {
            $idea->collaborators()->attach($user->id, [
                'joined_at' => now(),
                'contribution_points' => 0,
            ]);

            // Fire the real-time event
            CollaboratorJoined::dispatch($idea, $user);

            // Send notifications
            $this->notificationService->notifyCollaboratorJoined($idea, $user);

            // Award points for joining collaboration
            $this->pointsService->awardCollaborationJoined($user);

            // Queue email notification for the idea author
            if ($idea->author_id !== $user->id) {
                $this->notificationService->queueEmailNotifications($idea->author);
            }
        }
    }

    public function removeCollaborator(Idea $idea, User $user): void
    {
        $idea->collaborators()->detach($user->id);
    }

    public function upvoteIdea(Idea $idea, User $user): void
    {
        if (! $idea->hasUserUpvoted($user)) {
            $idea->upvotes()->create([
                'user_id' => $user->id,
            ]);

            // Fire the real-time event
            IdeaUpvoted::dispatch($idea, $user);

            // Send notifications
            $this->notificationService->notifyIdeaUpvoted($idea, $user);

            // Award points to idea author
            if ($idea->author_id !== $user->id) {
                $this->pointsService->awardIdeaUpvoted($idea->author);
            }

            // Queue email notification for the idea author
            if ($idea->author_id !== $user->id) {
                $this->notificationService->queueEmailNotifications($idea->author);
            }
        }
    }

    public function removeUpvote(Idea $idea, User $user): void
    {
        $idea->upvotes()->where('user_id', $user->id)->delete();
    }

    public function getIdeas(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Idea::with(['author', 'category', 'collaborators', 'upvotes'])
            ->published();

        if (isset($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['collaboration_enabled'])) {
            $query->collaborationEnabled();
        }

        if (isset($filters['author_id'])) {
            $query->where('author_id', $filters['author_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('problem_statement', 'like', "%{$search}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';

        if ($sortBy === 'upvotes') {
            $query->withCount('upvotes')->orderBy('upvotes_count', $sortDirection);
        } elseif ($sortBy === 'collaborators') {
            $query->withCount('collaborators')->orderBy('collaborators_count', $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($perPage);
    }

    public function getUserIdeas(User $user, array $filters = []): Collection
    {
        $query = $user->ideas();

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        return $query->with(['category', 'collaborators', 'upvotes'])->get();
    }

    public function getCollaborationIdeas(User $user): Collection
    {
        return Idea::whereHas('collaborators', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['author', 'category', 'upvotes'])->get();
    }

    public function getIdeaStats(Idea $idea): array
    {
        return [
            'total_upvotes' => $idea->getTotalUpvotes(),
            'total_collaborators' => $idea->getTotalCollaborators(),
            'total_suggestions' => $idea->getTotalSuggestions(),
            'total_revisions' => $idea->revisions()->count(),
            'is_collaboration_enabled' => $idea->collaboration_enabled,
            'status' => $idea->status,
        ];
    }

    protected function createRevision(Idea $idea, User $user, int $revisionNumber, string $summary, array $newData, array $oldData = [], array $changes = []): IdeaRevision
    {
        return IdeaRevision::create([
            'idea_id' => $idea->id,
            'user_id' => $user->id,
            'revision_number' => $revisionNumber,
            'changes' => $changes,
            'change_summary' => $summary,
            'previous_data' => $oldData,
            'new_data' => $newData,
        ]);
    }

    protected function calculateChanges(array $oldData, array $newData): array
    {
        $changes = [];

        foreach ($newData as $key => $value) {
            if (! isset($oldData[$key]) || $oldData[$key] !== $value) {
                $changes[$key] = [
                    'old' => $oldData[$key] ?? null,
                    'new' => $value,
                ];
            }
        }

        return $changes;
    }
}
