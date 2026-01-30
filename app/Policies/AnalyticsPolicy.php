<?php

namespace App\Policies;

use App\Models\User;

class AnalyticsPolicy
{
    /**
     * Determine whether the user can view analytics.
     */
    public function viewAnalytics(User $user): bool
    {
        // Allow admins and staff to view analytics
        return $user->hasRole('admin') || $user->hasRole('staff');
    }

    /**
     * Determine whether the user can export analytics data.
     */
    public function exportAnalytics(User $user): bool
    {
        // Only admins can export analytics data
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view system-wide analytics.
     */
    public function viewSystemAnalytics(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('staff');
    }

    /**
     * Determine whether the user can view user comparison analytics.
     */
    public function viewUserComparison(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view detailed user analytics.
     */
    public function viewUserDetails(User $user, User $targetUser): bool
    {
        // Users can view their own analytics, admins can view anyone's
        return $user->id === $targetUser->id || $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
