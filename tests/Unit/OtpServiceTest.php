<?php

use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('generates valid OTP code', function () {
    $service = new OtpService();

    // We can't directly test the private method, but we can test the overall functionality
    $email = 'test@example.com';

    $service->generateAndSendOtp($email);

    $otp = Otp::where('email', $email)->first();
    expect($otp)->not->toBeNull();
    expect($otp->otp)->toMatch('/^\d{6}$/');
});

test('validates correct OTP', function () {
    $service = new OtpService();
    $email = 'test@example.com';
    $otpCode = '123456';

    Otp::create([
        'email' => $email,
        'otp' => $otpCode,
        'expires_at' => now()->addMinutes(10),
    ]);

    $result = $service->validateOtp($email, $otpCode);

    expect($result)->not->toBeNull();
    expect($result->otp)->toBe($otpCode);
});

test('rejects expired OTP', function () {
    $service = new OtpService();
    $email = 'test@example.com';
    $otpCode = '123456';

    Otp::create([
        'email' => $email,
        'otp' => $otpCode,
        'expires_at' => now()->subMinutes(1), // Expired
    ]);

    $result = $service->validateOtp($email, $otpCode);

    expect($result)->toBeNull();
});

test('finds existing user', function () {
    $service = new OtpService();
    $user = User::factory()->create(['email' => 'test@example.com']);

    $foundUser = $service->findOrCreateUser('test@example.com');

    expect($foundUser->id)->toBe($user->id);
});

test('creates new user when not found', function () {
    $service = new OtpService();
    $email = 'newuser@example.com';

    $user = $service->findOrCreateUser($email);

    expect($user->email)->toBe($email);
    expect($user->email_verified_at)->not->toBeNull();
    $this->assertDatabaseHas('users', ['email' => $email]);});