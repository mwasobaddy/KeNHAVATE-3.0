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
        return Inertia::render('onboarding/user');
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
