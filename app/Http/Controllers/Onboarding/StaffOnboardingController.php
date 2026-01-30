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
        return Inertia::render('onboarding/staff');
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
