<?php

namespace App\Events;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IdeaUpvoteRemoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Idea $idea;

    public User $user;

    public function __construct(Idea $idea, User $user)
    {
        $this->idea = $idea;
        $this->user = $user;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('idea.'.$this->idea->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'idea.upvote_removed';
    }

    public function broadcastWith(): array
    {
        return [
            'idea' => [
                'id' => $this->idea->id,
                'title' => $this->idea->title,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
        ];
    }
}
