<?php

namespace App\Policies;

use App\Models\Idea;
use App\Models\User;

class IdeaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // All authenticated users can view ideas
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Idea $idea): bool
    {
        return true; // All authenticated users can view individual ideas
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create ideas
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Idea $idea): bool
    {
        // Only the author can update their own ideas, and only if they're in draft status
        return $idea->author_id === $user->id && $idea->isDraft();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Idea $idea): bool
    {
        // Only the author can delete their own ideas, and only if they're in draft status
        return $idea->author_id === $user->id && $idea->isDraft();
    }

    /**
     * Determine whether the user can submit the idea for review.
     */
    public function submit(User $user, Idea $idea): bool
    {
        return $idea->author_id === $user->id && $idea->isDraft();
    }

    /**
     * Determine whether the user can enable collaboration on the idea.
     */
    public function enableCollaboration(User $user, Idea $idea): bool
    {
        return $idea->author_id === $user->id && in_array($idea->status, ['submitted', 'under_review']);
    }

    /**
     * Determine whether the user can disable collaboration on the idea.
     */
    public function disableCollaboration(User $user, Idea $idea): bool
    {
        return $idea->author_id === $user->id;
    }

    /**
     * Determine whether the user can manage collaborators.
     */
    public function manageCollaborators(User $user, Idea $idea): bool
    {
        return $idea->author_id === $user->id && $idea->collaboration_enabled;
    }

    /**
     * Determine whether the user can upvote the idea.
     */
    public function upvote(User $user, Idea $idea): bool
    {
        // Users cannot upvote their own ideas
        return $idea->author_id !== $user->id && ! $idea->hasUserUpvoted($user);
    }

    /**
     * Determine whether the user can remove their upvote.
     */
    public function removeUpvote(User $user, Idea $idea): bool
    {
        return $idea->hasUserUpvoted($user);
    }

    /**
     * Determine whether the user can join as a collaborator.
     */
    public function joinCollaboration(User $user, Idea $idea): bool
    {
        return $idea->canBeCollaboratedOn() &&
               $idea->author_id !== $user->id &&
               ! $idea->isUserCollaborator($user);
    }

    /**
     * Determine whether the user can leave collaboration.
     */
    public function leaveCollaboration(User $user, Idea $idea): bool
    {
        return $idea->isUserCollaborator($user);
    }

    /**
     * Determine whether the user can approve/reject the idea (staff/admin/SME with review permissions).
     */
    public function review(User $user, Idea $idea): bool
    {
        // Check if user has review permission and idea is in reviewable status
        if (! $user->can('idea.review') || ! in_array($idea->status, ['submitted', 'under_review'])) {
            return false;
        }

        // SMEs cannot review their own ideas
        if ($user->hasRole('sme') && $idea->author_id === $user->id) {
            return false;
        }

        return true;
    }

    /**
     * Determine whether the user can implement the idea (admin with implementation permissions).
     */
    public function implement(User $user, Idea $idea): bool
    {
        return $user->can('idea.implement') && $idea->status === 'approved';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Idea $idea): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Idea $idea): bool
    {
        return false;
    }
}
