<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIdeaRequest;
use App\Http\Requests\UpdateIdeaRequest;
use App\Models\Idea;
use App\Models\IdeaCategory;
use App\Services\IdeaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IdeaController extends Controller
{
    public function __construct(
        private IdeaService $ideaService
    ) {}

    /**
     * Display a listing of ideas.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only([
            'category_id', 'status', 'collaboration_enabled', 'search',
            'sort_by', 'sort_direction',
        ]);

        $ideas = $this->ideaService->getIdeas($filters);

        $categories = IdeaCategory::all();

        return Inertia::render('ideas/index', [
            'ideas' => $ideas,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new idea.
     */
    public function create(): Response
    {
        $categories = IdeaCategory::all();

        return Inertia::render('ideas/create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created idea in storage.
     */
    public function store(StoreIdeaRequest $request): RedirectResponse
    {
        try {
            $idea = $this->ideaService->createIdea(
                $request->validated(),
                $request->user()
            );

            return redirect()
                ->route('ideas.show', $idea)
                ->with('success', 'Idea created successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create idea. Please try again.']);
        }
    }

    /**
     * Display the specified idea.
     */
    public function show(Idea $idea): Response
    {
        $idea->load([
            'author',
            'category',
            'collaborators',
            'upvotes',
            'revisions' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        $stats = $this->ideaService->getIdeaStats($idea);

        $canEdit = $idea->canBeEditedBy(request()->user());
        $canCollaborate = $idea->canBeCollaboratedOn();
        $hasUpvoted = $idea->hasUserUpvoted(request()->user());
        $isCollaborator = $idea->isUserCollaborator(request()->user());

        return Inertia::render('ideas/show', [
            'idea' => $idea,
            'stats' => $stats,
            'canEdit' => $canEdit,
            'canCollaborate' => $canCollaborate,
            'hasUpvoted' => $hasUpvoted,
            'isCollaborator' => $isCollaborator,
        ]);
    }

    /**
     * Show the form for editing the specified idea.
     */
    public function edit(Idea $idea): Response
    {
        $this->authorize('update', $idea);

        $categories = IdeaCategory::all();

        return Inertia::render('ideas/edit', [
            'idea' => $idea,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified idea in storage.
     */
    public function update(UpdateIdeaRequest $request, Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        try {
            $this->ideaService->updateIdea($idea, $request->validated(), $request->user());

            return redirect()
                ->route('ideas.show', $idea)
                ->with('success', 'Idea updated successfully!');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update idea. Please try again.']);
        }
    }

    /**
     * Remove the specified idea from storage.
     */
    public function destroy(Idea $idea): RedirectResponse
    {
        $this->authorize('delete', $idea);

        try {
            $idea->delete();

            return redirect()
                ->route('ideas.index')
                ->with('success', 'Idea deleted successfully!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete idea. Please try again.']);
        }
    }

    /**
     * Submit an idea for review.
     */
    public function submit(Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        if (! $idea->isDraft()) {
            return back()->withErrors(['error' => 'Only draft ideas can be submitted.']);
        }

        try {
            $this->ideaService->submitIdea($idea);

            return redirect()
                ->route('ideas.show', $idea)
                ->with('success', 'Idea submitted for review!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to submit idea. Please try again.']);
        }
    }

    /**
     * Enable collaboration on an idea.
     */
    public function enableCollaboration(Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        try {
            $this->ideaService->enableCollaboration($idea);

            return redirect()
                ->route('ideas.show', $idea)
                ->with('success', 'Collaboration enabled!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to enable collaboration. Please try again.']);
        }
    }

    /**
     * Disable collaboration on an idea.
     */
    public function disableCollaboration(Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        try {
            $this->ideaService->disableCollaboration($idea);

            return redirect()
                ->route('ideas.show', $idea)
                ->with('success', 'Collaboration disabled!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to disable collaboration. Please try again.']);
        }
    }

    /**
     * Upvote an idea.
     */
    public function upvote(Idea $idea): RedirectResponse
    {
        try {
            $this->ideaService->upvoteIdea($idea, request()->user());

            return back()->with('success', 'Idea upvoted!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to upvote idea. Please try again.']);
        }
    }

    /**
     * Remove upvote from an idea.
     */
    public function removeUpvote(Idea $idea): RedirectResponse
    {
        try {
            $this->ideaService->removeUpvote($idea, request()->user());

            return back()->with('success', 'Upvote removed!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to remove upvote. Please try again.']);
        }
    }

    /**
     * Add a collaborator to an idea.
     */
    public function addCollaborator(Request $request, Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $this->ideaService->addCollaborator($idea, $user);

            return back()->with('success', 'Collaborator added!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to add collaborator. Please try again.']);
        }
    }

    /**
     * Remove a collaborator from an idea.
     */
    public function removeCollaborator(Request $request, Idea $idea): RedirectResponse
    {
        $this->authorize('update', $idea);

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            $user = \App\Models\User::findOrFail($request->user_id);
            $this->ideaService->removeCollaborator($idea, $user);

            return back()->with('success', 'Collaborator removed!');
        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to remove collaborator. Please try again.']);
        }
    }
}
