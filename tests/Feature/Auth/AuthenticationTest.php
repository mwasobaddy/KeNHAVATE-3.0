<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('users can authenticate using OTP', function () {
    $user = User::factory()->create();

    // Send OTP
    $response = $this->post(route('login.send'), [
        'email' => $user->email,
    ]);

    $response->assertOk();

    // Get the OTP from database
    $otpRecord = \App\Models\Otp::where('email', $user->email)->latest()->first();

    // Verify OTP
    $response = $this->post(route('otp.verify'), [
        'email' => $user->email,
        'otp' => $otpRecord->otp,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    // Test that middleware redirects to onboarding for incomplete users
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('user.onboarding'));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->markTestSkipped('Two-factor authentication not implemented with OTP flow yet.');
});

test('users can not authenticate with invalid OTP', function () {
    $user = User::factory()->create();

    $this->post(route('login.send'), [
        'email' => $user->email,
    ]);

    $this->post(route('otp.verify'), [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $this->assertGuest();
    $response->assertRedirect(route('home'));
});

test('users are rate limited after multiple failed OTP verification attempts', function () {
    $user = User::factory()->create();

    // Send OTP first
    $this->post(route('login.send'), [
        'email' => $user->email,
    ]);

    // Attempt verification 5 times with wrong OTP
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('otp.verify'), [
            'email' => $user->email,
            'otp' => '123456', // Wrong OTP
        ]);
    }

    // 6th attempt should be rate limited
    $response = $this->post(route('otp.verify'), [
        'email' => $user->email,
        'otp' => '123456',
    ]);

    $response->assertRedirect()
        ->assertSessionHasErrors(['otp' => 'Too many failed verification attempts. Please try again later.']);
});

test('users with active OTP session are redirected to OTP verification on login page refresh', function () {
    $user = User::factory()->create();

    // Send OTP to create session
    $this->post(route('login.send'), [
        'email' => $user->email,
    ]);

    // Simulate page refresh - should redirect to OTP verification
    $response = $this->get(route('login'));

    $response->assertInertia(fn ($page) => $page
        ->component('auth/otp-verify')
        ->has('email')
        ->where('email', $user->email)
    );
});

test('users can resend OTP', function () {
    $user = User::factory()->create();

    // Send initial OTP
    $this->post(route('login.send'), [
        'email' => $user->email,
    ]);

    $initialOtpCount = \App\Models\Otp::where('email', $user->email)->count();

    // Resend OTP (should reuse existing valid OTP)
    $response = $this->post(route('otp.resend'), [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJson(['message' => 'OTP resent successfully']);

    // Should not have created a new OTP record since existing one is valid
    $newOtpCount = \App\Models\Otp::where('email', $user->email)->count();
    expect($newOtpCount)->toBe($initialOtpCount);

    // No OTPs should be marked as used since we're reusing the valid one
    $usedOtps = \App\Models\Otp::where('email', $user->email)
        ->whereNotNull('used_at')
        ->count();
    expect($usedOtps)->toBe(0);
});

test('OTP countdown persists across page refreshes', function () {
    $user = User::factory()->create();

    // Send OTP to create session with countdown start time
    $this->post(route('login.send'), [
        'email' => $user->email,
    ]);

    // Simulate waiting 10 seconds (by manipulating session timestamp)
    $originalStart = session('otp_countdown_start');
    session(['otp_countdown_start' => now()->timestamp - 10]);

    // Simulate page refresh - should redirect to OTP verification with reduced countdown
    $response = $this->get(route('login'));

    $response->assertInertia(fn ($page) => $page
        ->component('auth/otp-verify')
        ->has('email')
        ->has('remainingSeconds')
        ->where('email', $user->email)
        ->where('remainingSeconds', 50) // 60 - 10 = 50
    );

    // Restore original timestamp for cleanup
    session(['otp_countdown_start' => $originalStart]);
});

test('onboarding completion creates audit logs with model information', function () {
    // Create roles first
    \Spatie\Permission\Models\Role::create(['name' => 'user']);
    \Spatie\Permission\Models\Role::create(['name' => 'staff']);

    $user = User::factory()->create([
        'onboarding_completed' => false,
    ]);

    // Complete user onboarding
    $response = $this->actingAs($user)->post(route('user.onboarding.store'), [
        'name' => 'John Doe',
        'mobile' => '0712345678',
        'id_number' => '12345678',
        'gender' => 'male',
    ]);

    $response->assertRedirect(route('dashboard'));

    // Check that audit log was created with correct model information
    $audit = \App\Models\Audit::where('user_id', $user->id)
        ->where('event', 'user_onboarding_completed')
        ->first();

    expect($audit)->not->toBeNull();
    expect($audit->model_type)->toBe(\App\Models\User::class);
    expect($audit->model_id)->toBe($user->id);
});