<?php

namespace App\Events;

use App\Models\Idea;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollaboratorJoined implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Idea $idea;

    public User $collaborator;

    /**
     * Create a new event instance.
     */
    public function __construct(Idea $idea, User $collaborator)
    {
        $this->idea = $idea;
        $this->collaborator = $collaborator;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('idea.'.$this->idea->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'collaborator.joined';
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
            ],
            'collaborator' => [
                'id' => $this->collaborator->id,
                'name' => $this->collaborator->name,
                'email' => $this->collaborator->email,
            ],
        ];
    }
}
