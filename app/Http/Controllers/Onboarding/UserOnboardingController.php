<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\UserOnboardingRequest;
use App\Services\OnboardingService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserOnboardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService
    ) {}

    public function show()
    {
        // Check if user just logged in with Google and show success message
        $googleLoginSuccess = session('google_login_success', false);
        if ($googleLoginSuccess) {
            session()->forget('google_login_success'); // Clear it so it doesn't show again
        }

        return Inertia::render('onboarding/user', [
            'google_login_success' => $googleLoginSuccess,
        ]);
    }

    public function store(UserOnboardingRequest $request)
    {
        $this->onboardingService->completeUserOnboarding(
            Auth::user(),
            $request->validated()
        );

        return redirect()->intended(route('dashboard'));
    }
}
