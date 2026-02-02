<?php

namespace App\Http\Controllers\Ideas;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSuggestionRequest;
use App\Models\Idea;
use App\Models\Suggestion;
use App\Services\SuggestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuggestionController extends Controller
{
    public function __construct(
        private SuggestionService $suggestionService
    ) {}

    /**
     * Display suggestions for a specific idea.
     */
    public function index(Idea $idea, Request $request): Response
    {
        $filters = $request->only(['type', 'status']);
        $suggestions = $this->suggestionService->getPaginatedSuggestionsForIdea($idea, $filters);

        $stats = $this->suggestionService->getSuggestionStats($idea);

        return Inertia::render('ideas/suggestions/index', [
            'idea' => $idea->load(['author', 'category']),
            'suggestions' => $suggestions,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * Store a new suggestion.
     */
    public function store(StoreSuggestionRequest $request, Idea $idea): RedirectResponse
    {
        if (! $idea->canBeCollaboratedOn()) {
            return back()->withErrors(['error' => 'This idea is not open for collaboration.']);
        }

        try {
            $parent = null;
            if ($request->has('parent_id') && $request->parent_id) {
                $parent = Suggestion::findOrFail($request->parent_id);
                // Ensure the parent belongs to the same idea
                if ($parent->idea_id !== $idea->id) {
                    return back()->withErrors(['error' => 'Invalid parent suggestion.']);
                }
            }

            $this->suggestionService->createSuggestion(
                $idea,
                $request->validated(),
                $request->user(),
                $parent
            );

            return back()->with('success', 'Suggestion added successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to add suggestion. Please try again.']);
        }
    }

    /**
     * Display a specific suggestion thread.
     */
    public function show(Idea $idea, Suggestion $suggestion): Response
    {
        // Ensure the suggestion belongs to the idea
        if ($suggestion->idea_id !== $idea->id) {
            abort(404);
        }

        $thread = $this->suggestionService->getSuggestionThread($suggestion);

        return Inertia::render('ideas/suggestions/show', [
            'idea' => $idea->load(['author', 'category']),
            'thread' => $thread,
            'suggestion' => $suggestion,
        ]);
    }

    /**
     * Accept a suggestion.
     */
    public function accept(Idea $idea, Suggestion $suggestion): RedirectResponse
    {
        // Ensure the suggestion belongs to the idea
        if ($suggestion->idea_id !== $idea->id) {
            abort(404);
        }

        $this->authorize('update', $idea);

        if (! $suggestion->canBeAcceptedBy(request()->user())) {
            return back()->withErrors(['error' => 'You cannot accept this suggestion.']);
        }

        try {
            $this->suggestionService->acceptSuggestion($suggestion, request()->user());

            return back()->with('success', 'Suggestion accepted!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to accept suggestion. Please try again.']);
        }
    }

    /**
     * Reject a suggestion.
     */
    public function reject(Idea $idea, Suggestion $suggestion): RedirectResponse
    {
        // Ensure the suggestion belongs to the idea
        if ($suggestion->idea_id !== $idea->id) {
            abort(404);
        }

        $this->authorize('update', $idea);

        if (! $suggestion->canBeRejectedBy(request()->user())) {
            return back()->withErrors(['error' => 'You cannot reject this suggestion.']);
        }

        try {
            $this->suggestionService->rejectSuggestion($suggestion);

            return back()->with('success', 'Suggestion rejected!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to reject suggestion. Please try again.']);
        }
    }

    /**
     * Get user's suggestions.
     */
    public function userSuggestions(Request $request): Response
    {
        $filters = $request->only(['idea_id', 'type', 'status']);
        $suggestions = $this->suggestionService->getUserSuggestions($request->user(), $filters);

        return Inertia::render('ideas/suggestions/user-suggestions', [
            'suggestions' => $suggestions,
            'filters' => $filters,
        ]);
    }
}
