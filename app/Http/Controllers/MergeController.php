<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Suggestion;
use App\Models\SuggestionConflict;
use App\Services\MergeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MergeController extends Controller
{
    public function __construct(
        private MergeService $mergeService
    ) {}

    /**
     * Analyze potential conflicts for merging suggestions.
     */
    public function analyzeConflicts(Request $request, Idea $idea): JsonResponse
    {
        $this->authorize('update', $idea);

        $suggestionIds = $request->input('suggestion_ids', []);
        $suggestions = Suggestion::whereIn('id', $suggestionIds)
            ->where('idea_id', $idea->id)
            ->where('is_accepted', false)
            ->get();

        if ($suggestions->isEmpty()) {
            return response()->json([
                'message' => 'No valid suggestions found for analysis',
                'conflicts' => [],
            ]);
        }

        $conflicts = $this->mergeService->analyzeConflicts($suggestions);

        return response()->json([
            'conflicts' => $conflicts,
            'suggestions_count' => $suggestions->count(),
            'can_merge' => count($conflicts) === 0,
        ]);
    }

    /**
     * Perform smart merge of suggestions.
     */
    public function mergeSuggestions(Request $request, Idea $idea): JsonResponse
    {
        $this->authorize('update', $idea);

        $request->validate([
            'suggestion_ids' => 'required|array|min:1',
            'suggestion_ids.*' => 'integer|exists:suggestions,id',
            'strategy' => 'sometimes|in:consensus,priority,latest',
            'auto_merge' => 'sometimes|boolean',
            'conflict_resolution' => 'sometimes|array',
        ]);

        $suggestionIds = $request->input('suggestion_ids');
        $suggestions = Suggestion::whereIn('id', $suggestionIds)
            ->where('idea_id', $idea->id)
            ->where('is_accepted', false)
            ->get();

        if ($suggestions->isEmpty()) {
            return response()->json([
                'message' => 'No valid suggestions found for merging',
            ], 422);
        }

        try {
            $merge = $this->mergeService->mergeSuggestions(
                $idea,
                $suggestions,
                $request->user(),
                $request->only(['strategy', 'auto_merge', 'conflict_resolution'])
            );

            return response()->json([
                'message' => 'Suggestions merged successfully',
                'merge' => $merge->load('mergedBy'),
                'applied_changes' => $merge->changes_applied,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to merge suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get merge history for an idea.
     */
    public function getMergeHistory(Idea $idea): JsonResponse
    {
        $this->authorize('view', $idea);

        $merges = $this->mergeService->getMergeHistory($idea);

        return response()->json([
            'merges' => $merges,
        ]);
    }

    /**
     * Get unresolved conflicts for an idea.
     */
    public function getUnresolvedConflicts(Idea $idea): JsonResponse
    {
        $this->authorize('view', $idea);

        $conflicts = $this->mergeService->getUnresolvedConflicts($idea);

        return response()->json([
            'conflicts' => $conflicts->load(['suggestion1.author', 'suggestion2.author']),
        ]);
    }

    /**
     * Resolve a conflict.
     */
    public function resolveConflict(Request $request, SuggestionConflict $conflict): JsonResponse
    {
        $this->authorize('update', $conflict->idea);

        $request->validate([
            'resolution' => 'required|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $conflict->resolve($request->user(), $request->input('notes'));

            return response()->json([
                'message' => 'Conflict resolved successfully',
                'conflict' => $conflict->load('resolvedBy'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to resolve conflict',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get merge suggestions (AI-powered recommendations).
     */
    public function getMergeSuggestions(Idea $idea): JsonResponse
    {
        $this->authorize('view', $idea);

        $pendingSuggestions = $idea->suggestions()
            ->where('is_accepted', false)
            ->where('is_rejected', false)
            ->with('author')
            ->get();

        if ($pendingSuggestions->count() < 2) {
            return response()->json([
                'suggestions' => [],
                'message' => 'Need at least 2 suggestions for merge analysis',
            ]);
        }

        // Analyze conflicts
        $conflicts = $this->mergeService->analyzeConflicts($pendingSuggestions);

        // Generate merge recommendations
        $recommendations = $this->generateMergeRecommendations($pendingSuggestions, $conflicts);

        return response()->json([
            'suggestions' => $recommendations,
            'conflicts_count' => count($conflicts),
            'total_suggestions' => $pendingSuggestions->count(),
        ]);
    }

    /**
     * Generate merge recommendations based on suggestions and conflicts.
     */
    protected function generateMergeRecommendations(Collection $suggestions, array $conflicts): array
    {
        $recommendations = [];

        // Recommendation 1: Merge similar suggestions
        $similarGroups = $this->mergeService->groupSimilarSuggestions($suggestions);
        $largeGroups = $similarGroups->filter(function ($group) {
            return $group->count() >= 2;
        });

        if ($largeGroups->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'consensus_merge',
                'title' => 'Merge Similar Suggestions',
                'description' => "Found {$largeGroups->count()} groups of similar suggestions that can be consolidated",
                'priority' => 'high',
                'suggested_suggestion_ids' => $largeGroups->flatten()->pluck('id')->toArray(),
                'strategy' => 'consensus',
            ];
        }

        // Recommendation 2: Priority-based merge
        $highPrioritySuggestions = $suggestions->filter(function ($suggestion) {
            return $suggestion->author->points()->sum('amount') > 100; // High reputation authors
        });

        if ($highPrioritySuggestions->count() >= 3) {
            $recommendations[] = [
                'type' => 'priority_merge',
                'title' => 'Priority-Based Merge',
                'description' => 'Merge suggestions from high-reputation contributors first',
                'priority' => 'medium',
                'suggested_suggestion_ids' => $highPrioritySuggestions->pluck('id')->toArray(),
                'strategy' => 'priority',
            ];
        }

        // Recommendation 3: Resolve conflicts first
        if (count($conflicts) > 0) {
            $recommendations[] = [
                'type' => 'conflict_resolution',
                'title' => 'Resolve Conflicts First',
                'description' => 'Found '.count($conflicts).' conflicts that need resolution before merging',
                'priority' => 'high',
                'action_required' => 'resolve_conflicts',
            ];
        }

        // Recommendation 4: Auto-merge non-conflicting suggestions
        $nonConflictingSuggestions = $this->getNonConflictingSuggestions($suggestions, $conflicts);
        if ($nonConflictingSuggestions->count() >= 2) {
            $recommendations[] = [
                'type' => 'auto_merge',
                'title' => 'Auto-Merge Safe Suggestions',
                'description' => 'Automatically merge suggestions that don\'t conflict with each other',
                'priority' => 'low',
                'suggested_suggestion_ids' => $nonConflictingSuggestions->pluck('id')->toArray(),
                'auto_merge' => true,
            ];
        }

        return $recommendations;
    }

    /**
     * Get suggestions that don't have conflicts.
     */
    protected function getNonConflictingSuggestions(Collection $suggestions, array $conflicts): Collection
    {
        $conflictingIds = [];

        foreach ($conflicts as $conflict) {
            if (isset($conflict['suggestion_1'])) {
                $conflictingIds[] = $conflict['suggestion_1']->id;
                $conflictingIds[] = $conflict['suggestion_2']->id;
            }
        }

        return $suggestions->filter(function ($suggestion) use ($conflictingIds) {
            return ! in_array($suggestion->id, $conflictingIds);
        });
    }
}
