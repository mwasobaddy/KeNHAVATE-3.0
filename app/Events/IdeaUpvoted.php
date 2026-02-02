<?php

namespace App\Events;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IdeaUpvoted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Idea $idea;

    public User $user;

    /**
     * Create a new event instance.
     */
    public function __construct(Idea $idea, User $user)
    {
        $this->idea = $idea;
        $this->user = $user;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('idea.'.$this->idea->id),
        ];

        $recipientIds = collect([$this->idea->author_id])
            ->merge($this->idea->collaborators()->pluck('users.id'))
            ->unique();

        foreach ($recipientIds as $userId) {
            $channels[] = new PrivateChannel('App.Models.User.'.$userId);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'idea.upvoted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'idea' => [
                'id' => $this->idea->id,
                'title' => $this->idea->title,
                'upvotes_count' => $this->idea->upvotes_count,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
        ];
    }
}
