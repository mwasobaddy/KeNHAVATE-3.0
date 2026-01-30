<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create(['onboarding_completed' => true]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('users without completed onboarding are redirected to user onboarding', function () {
    $user = User::factory()->create(['onboarding_completed' => false, 'email' => 'user@example.com']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('user.onboarding'));
});

test('staff users without completed onboarding are redirected to staff onboarding', function () {
    $user = User::factory()->create(['onboarding_completed' => false, 'email' => 'staff@kenha.co.ke']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('staff.onboarding'));
});