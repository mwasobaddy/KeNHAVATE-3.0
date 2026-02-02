<?php

use App\Models\Idea;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('idea.{ideaId}', function (User $user, int $ideaId) {
    $idea = Idea::find($ideaId);

    if (!$idea) {
        return false;
    }

    // Allow if user is the author or a collaborator
    return $idea->author_id === $user->id || $idea->collaborators()->where('user_id', $user->id)->exists();
});