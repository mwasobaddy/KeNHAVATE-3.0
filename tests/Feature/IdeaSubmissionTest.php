<?php

use App\Models\Idea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('idea submission redirects for regular form requests', function () {
    $user = User::factory()->create();
    $idea = Idea::factory()->create(['author_id' => $user->id, 'status' => 'draft']);

    $response = $this->actingAs($user)
        ->withoutMiddleware()
        ->post(route('ideas.submit', $idea));

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Idea submitted for review!',
            'idea' => [
                'id' => $idea->id,
                'status' => 'submitted',
            ],
        ]);

    $idea->refresh();
    expect($idea->status)->toBe('submitted');
});

test('idea submission returns JSON for Inertia requests', function () {
    $user = User::factory()->create();
    $idea = Idea::factory()->create(['author_id' => $user->id, 'status' => 'draft']);

    $response = $this->actingAs($user)
        ->withoutMiddleware()
        ->post(route('ideas.submit', $idea), [], ['X-Inertia' => 'true']);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Idea submitted for review!',
            'idea' => [
                'id' => $idea->id,
                'status' => 'submitted',
            ],
        ]);

    $idea->refresh();
    expect($idea->status)->toBe('submitted');
});

test('idea submission fails for non-owner', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $idea = Idea::factory()->create(['author_id' => $otherUser->id, 'status' => 'draft']);

    $response = $this->actingAs($user)
        ->withoutMiddleware()
        ->post(route('ideas.submit', $idea));

    $response->assertForbidden();

    $idea->refresh();
    expect($idea->status)->toBe('draft');
});
