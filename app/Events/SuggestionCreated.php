<?php

namespace App\Events;

use App\Models\Suggestion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuggestionCreated implements ShouldBroadcast
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
        return 'suggestion.created';
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
                'created_at' => $this->suggestion->created_at,
            ],
        ];
    }
}
