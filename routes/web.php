<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Onboarding\StaffOnboardingController;
use App\Http\Controllers\Onboarding\UserOnboardingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('auth/login');
})->name('home');

Route::get('login', [OtpController::class, 'showLoginForm'])->name('login');
Route::post('login', [OtpController::class, 'sendOtp'])->name('login.send');
Route::post('otp/verify', [OtpController::class, 'verifyOtp'])->name('otp.verify');
Route::post('otp/resend', [OtpController::class, 'resendOtp'])->name('otp.resend');

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('onboarding/user', [UserOnboardingController::class, 'show'])->name('user.onboarding');
    Route::post('onboarding/user', [UserOnboardingController::class, 'store'])->name('user.onboarding.store');

    Route::get('onboarding/staff', [StaffOnboardingController::class, 'show'])->name('staff.onboarding');
    Route::post('onboarding/staff', [StaffOnboardingController::class, 'store'])->name('staff.onboarding.store');
});

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
