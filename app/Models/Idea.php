<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Idea extends Model
{
    protected $fillable = [
        'author_id',
        'title',
        'description',
        'category_id',
        'problem_statement',
        'proposed_solution',
        'cost_benefit_analysis',
        'proposal_document_path',
        'collaboration_enabled',
        'status',
        'current_review_cycle',
        'submitted_at',
        'implemented_at',
    ];

    protected $casts = [
        'collaboration_enabled' => 'boolean',
        'current_review_cycle' => 'integer',
        'submitted_at' => 'datetime',
        'implemented_at' => 'datetime',
    ];

    // Relationships
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IdeaCategory::class, 'category_id');
    }

    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'idea_collaborators')
            ->withPivot('joined_at', 'contribution_points')
            ->withTimestamps();
    }

    public function suggestions(): HasMany
    {
        return $this->hasMany(Suggestion::class);
    }

    public function upvotes(): HasMany
    {
        return $this->hasMany(IdeaUpvote::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(IdeaRevision::class);
    }

    public function merges(): HasMany
    {
        return $this->hasMany(SuggestionMerge::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(SuggestionConflict::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', '!=', 'draft');
    }

    public function scopeCollaborationEnabled($query)
    {
        return $query->where('collaboration_enabled', true);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Helper methods
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isUnderReview(): bool
    {
        return $this->status === 'under_review';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isImplemented(): bool
    {
        return $this->status === 'implemented';
    }

    public function canBeEditedBy(User $user): bool
    {
        return $this->author_id === $user->id && $this->isDraft();
    }

    public function canBeCollaboratedOn(): bool
    {
        return $this->collaboration_enabled && in_array($this->status, ['submitted', 'under_review']);
    }

    public function getTotalUpvotes(): int
    {
        return $this->upvotes()->count();
    }

    public function getTotalCollaborators(): int
    {
        return $this->collaborators()->count();
    }

    public function getTotalSuggestions(): int
    {
        return $this->suggestions()->count();
    }

    public function hasUserUpvoted(User $user): bool
    {
        return $this->upvotes()->where('user_id', $user->id)->exists();
    }

    public function isUserCollaborator(User $user): bool
    {
        return $this->collaborators()->where('user_id', $user->id)->exists();
    }

    public function submit(): void
    {
        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'status' => 'rejected',
        ]);
    }

    public function implement(): void
    {
        $this->update([
            'status' => 'implemented',
            'implemented_at' => now(),
        ]);
    }

    public function enableCollaboration(): void
    {
        $this->update(['collaboration_enabled' => true]);
    }

    public function disableCollaboration(): void
    {
        $this->update(['collaboration_enabled' => false]);
    }
}
