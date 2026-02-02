<?php

namespace App\Events;

use App\Models\Suggestion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuggestionAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Suggestion $suggestion;

    /**
     * Create a new event instance.
     */
    public function __construct(Suggestion $suggestion)
    {
        $this->suggestion = $suggestion;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('idea.'.$this->suggestion->idea_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'suggestion.accepted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'suggestion' => [
                'id' => $this->suggestion->id,
                'content' => $this->suggestion->content,
                'type' => $this->suggestion->type,
                'author' => [
                    'id' => $this->suggestion->author->id,
                    'name' => $this->suggestion->author->name,
                ],
                'accepted_at' => $this->suggestion->accepted_at,
            ],
        ];
    }
}
