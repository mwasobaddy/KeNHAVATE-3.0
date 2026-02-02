<?php

namespace App\Events;

use App\Models\Suggestion;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuggestionCreated implements ShouldBroadcastNow
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
        $channels = [
            new PrivateChannel('idea.'.$this->suggestion->idea_id),
        ];

        $recipientIds = collect([$this->suggestion->idea->author_id])
            ->merge($this->suggestion->idea->collaborators()->pluck('users.id'))
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
                'idea_id' => $this->suggestion->idea_id,
                'author' => [
                    'id' => $this->suggestion->author->id,
                    'name' => $this->suggestion->author->name,
                ],
                'created_at' => $this->suggestion->created_at,
            ],
        ];
    }
}
