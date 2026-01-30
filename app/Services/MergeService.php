<?php

namespace App\Services;

use App\Models\Idea;
use App\Models\Suggestion;
use App\Models\SuggestionConflict;
use App\Models\SuggestionMerge;
use App\Models\User;
use Illuminate\Support\Collection;

class MergeService
{
    public function __construct(
        private PointsService $pointsService
    ) {}

    /**
     * Analyze suggestions for potential conflicts before merging.
     */
    public function analyzeConflicts(Collection $suggestions): array
    {
        $conflicts = [];

        // Check for content overlap conflicts
        $contentConflicts = $this->detectContentConflicts($suggestions);
        $conflicts = array_merge($conflicts, $contentConflicts);

        // Check for logical conflicts (e.g., contradictory suggestions)
        $logicalConflicts = $this->detectLogicalConflicts($suggestions);
        $conflicts = array_merge($conflicts, $logicalConflicts);

        return $conflicts;
    }

    /**
     * Detect content overlap conflicts between suggestions.
     */
    protected function detectContentConflicts(Collection $suggestions): array
    {
        $conflicts = [];

        foreach ($suggestions as $i => $suggestion1) {
            for ($j = $i + 1; $j < $suggestions->count(); $j++) {
                $suggestion2 = $suggestions[$j];

                $similarity = $this->calculateTextSimilarity(
                    $suggestion1->content,
                    $suggestion2->content
                );

                if ($similarity > 0.7) { // 70% similarity threshold
                    $conflicts[] = [
                        'type' => 'content_overlap',
                        'suggestion_1' => $suggestion1,
                        'suggestion_2' => $suggestion2,
                        'similarity' => $similarity,
                        'description' => 'Suggestions have similar content that may be redundant',
                    ];
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect logical conflicts between suggestions.
     */
    protected function detectLogicalConflicts(Collection $suggestions): array
    {
        $conflicts = [];

        // Check for contradictory suggestions
        $positiveSuggestions = $suggestions->filter(function ($suggestion) {
            return $this->isPositiveSuggestion($suggestion->content);
        });

        $negativeSuggestions = $suggestions->filter(function ($suggestion) {
            return $this->isNegativeSuggestion($suggestion->content);
        });

        if ($positiveSuggestions->isNotEmpty() && $negativeSuggestions->isNotEmpty()) {
            $conflicts[] = [
                'type' => 'logical_conflict',
                'description' => 'Conflicting suggestions: some suggest positive changes while others suggest removal/reduction',
                'positive_count' => $positiveSuggestions->count(),
                'negative_count' => $negativeSuggestions->count(),
            ];
        }

        return $conflicts;
    }

    /**
     * Perform smart merge of suggestions.
     */
    public function mergeSuggestions(Idea $idea, Collection $suggestions, User $user, array $options = []): SuggestionMerge
    {
        // Analyze conflicts first
        $conflicts = $this->analyzeConflicts($suggestions);

        // Create conflict records for any detected conflicts
        $conflictRecords = [];
        foreach ($conflicts as $conflict) {
            if (isset($conflict['suggestion_1'])) {
                $conflictRecords[] = SuggestionConflict::create([
                    'idea_id' => $idea->id,
                    'suggestion_1_id' => $conflict['suggestion_1']->id,
                    'suggestion_2_id' => $conflict['suggestion_2']->id,
                    'conflict_type' => $conflict['type'],
                    'conflict_description' => $conflict['description'],
                    'conflicting_values' => [
                        'similarity' => $conflict['similarity'] ?? null,
                    ],
                ]);
            }
        }

        // Apply merge strategy
        $mergeStrategy = $options['strategy'] ?? 'consensus';
        $changes = $this->applyMergeStrategy($suggestions, $mergeStrategy);

        // Create merge record
        $merge = SuggestionMerge::create([
            'idea_id' => $idea->id,
            'merged_by' => $user->id,
            'merged_suggestions' => $suggestions->pluck('id')->toArray(),
            'merge_summary' => $this->generateMergeSummary($suggestions, $changes),
            'changes_applied' => $changes,
            'merge_type' => $options['auto_merge'] ?? false ? 'auto' : 'manual',
            'has_conflicts' => count($conflicts) > 0,
            'conflict_resolution' => $options['conflict_resolution'] ?? null,
        ]);

        // Award points for performing merge
        $this->pointsService->awardMergePerformed($user);

        // Mark suggestions as merged
        $suggestions->each(function ($suggestion) {
            $suggestion->update(['is_accepted' => true]);
        });

        return $merge;
    }

    /**
     * Apply different merge strategies.
     */
    protected function applyMergeStrategy(Collection $suggestions, string $strategy): array
    {
        switch ($strategy) {
            case 'consensus':
                return $this->consensusMerge($suggestions);
            case 'priority':
                return $this->priorityMerge($suggestions);
            case 'latest':
                return $this->latestMerge($suggestions);
            default:
                return $this->consensusMerge($suggestions);
        }
    }

    /**
     * Consensus-based merging (most agreed upon suggestions).
     */
    protected function consensusMerge(Collection $suggestions): array
    {
        $changes = [];

        // Group similar suggestions
        $groupedSuggestions = $this->groupSimilarSuggestions($suggestions);

        foreach ($groupedSuggestions as $group) {
            if ($group->count() >= 2) { // At least 2 similar suggestions
                $changes[] = [
                    'type' => 'consensus_change',
                    'content' => $group->first()->content,
                    'support_count' => $group->count(),
                    'authors' => $group->pluck('author.name')->toArray(),
                ];
            }
        }

        return $changes;
    }

    /**
     * Priority-based merging (based on author reputation/points).
     */
    protected function priorityMerge(Collection $suggestions): array
    {
        $changes = [];

        // Sort by author points (assuming higher points = higher priority)
        $sortedSuggestions = $suggestions->sortByDesc(function ($suggestion) {
            return $suggestion->author->points()->sum('amount');
        });

        // Take top suggestions
        $topSuggestions = $sortedSuggestions->take(3);

        foreach ($topSuggestions as $suggestion) {
            $changes[] = [
                'type' => 'priority_change',
                'content' => $suggestion->content,
                'author_points' => $suggestion->author->points()->sum('amount'),
                'author' => $suggestion->author->name,
            ];
        }

        return $changes;
    }

    /**
     * Latest suggestions first.
     */
    protected function latestMerge(Collection $suggestions): array
    {
        $changes = [];

        $latestSuggestions = $suggestions->sortByDesc('created_at')->take(5);

        foreach ($latestSuggestions as $suggestion) {
            $changes[] = [
                'type' => 'latest_change',
                'content' => $suggestion->content,
                'created_at' => $suggestion->created_at,
                'author' => $suggestion->author->name,
            ];
        }

        return $changes;
    }

    /**
     * Group similar suggestions based on content similarity.
     */
    protected function groupSimilarSuggestions(Collection $suggestions): Collection
    {
        $groups = collect();

        foreach ($suggestions as $suggestion) {
            $foundGroup = false;

            foreach ($groups as $group) {
                $similarity = $this->calculateTextSimilarity(
                    $suggestion->content,
                    $group->first()->content
                );

                if ($similarity > 0.6) { // 60% similarity threshold for grouping
                    $group->push($suggestion);
                    $foundGroup = true;
                    break;
                }
            }

            if (! $foundGroup) {
                $groups->push(collect([$suggestion]));
            }
        }

        return $groups;
    }

    /**
     * Calculate text similarity using simple string comparison.
     */
    protected function calculateTextSimilarity(string $text1, string $text2): float
    {
        $text1 = strtolower($text1);
        $text2 = strtolower($text2);

        // Simple Jaccard similarity
        $words1 = array_unique(str_word_count($text1, 1));
        $words2 = array_unique(str_word_count($text2, 1));

        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));

        return count($intersection) / count($union);
    }

    /**
     * Check if suggestion content is positive (additive).
     */
    protected function isPositiveSuggestion(string $content): bool
    {
        $positiveWords = ['add', 'include', 'implement', 'create', 'increase', 'expand', 'enhance'];
        $negativeWords = ['remove', 'delete', 'reduce', 'eliminate', 'exclude'];

        $content = strtolower($content);

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            if (str_contains($content, $word)) {
                $positiveCount++;
            }
        }

        foreach ($negativeWords as $word) {
            if (str_contains($content, $word)) {
                $negativeCount++;
            }
        }

        return $positiveCount > $negativeCount;
    }

    /**
     * Check if suggestion content is negative (subtractive).
     */
    protected function isNegativeSuggestion(string $content): bool
    {
        return ! $this->isPositiveSuggestion($content);
    }

    /**
     * Generate a summary of the merge operation.
     */
    protected function generateMergeSummary(Collection $suggestions, array $changes): string
    {
        $totalSuggestions = $suggestions->count();
        $totalChanges = count($changes);

        return "Merged {$totalSuggestions} suggestions into {$totalChanges} consolidated changes";
    }

    /**
     * Get merge history for an idea.
     */
    public function getMergeHistory(Idea $idea): Collection
    {
        return $idea->merges()->with('mergedBy')->orderByDesc('created_at')->get();
    }

    /**
     * Get unresolved conflicts for an idea.
     */
    public function getUnresolvedConflicts(Idea $idea): Collection
    {
        return $idea->conflicts()->where('resolution_status', 'unresolved')->get();
    }
}
