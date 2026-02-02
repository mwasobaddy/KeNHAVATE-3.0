<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SocialAuthService;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function __construct(
        private SocialAuthService $socialAuthService
    ) {}

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->user();

        $user = $this->socialAuthService->findOrCreateUser($googleUser);

        Auth::login($user);

        // Redirect to dashboard - middleware will handle onboarding check
        return redirect()->intended(route('dashboard'))->with('google_login_success', true);
    }
}
