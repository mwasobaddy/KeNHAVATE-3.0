<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StaffOnboardingRequest;
use App\Services\OnboardingService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class StaffOnboardingController extends Controller
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

        return Inertia::render('onboarding/staff', [
            'google_login_success' => $googleLoginSuccess,
        ]);
    }

    public function store(StaffOnboardingRequest $request)
    {
        $this->onboardingService->completeStaffOnboarding(
            Auth::user(),
            $request->validated()
        );

        return redirect()->intended(route('dashboard'));
    }
}
