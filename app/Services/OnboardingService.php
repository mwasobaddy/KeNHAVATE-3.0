<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Point;
use App\Models\PointsConfiguration;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;

class OnboardingService
{
    public function completeUserOnboarding(User $user, array $data): void
    {
        $user->update([
            'name' => $data['name'],
            'mobile' => $data['mobile'],
            'id_number' => $data['id_number'],
            'gender' => $data['gender'],
            'onboarding_completed' => true,
        ]);

        $user->assignRole('user');

        $this->awardPoints($user, 'first_login');
        $this->createAuditLog($user, 'user_onboarding_completed', request(), User::class, $user->id);
    }

    public function completeStaffOnboarding(User $user, array $data): void
    {
        $user->update([
            'work_email' => $data['work_email'],
            'onboarding_completed' => true,
        ]);

        $staff = Staff::create([
            'user_id' => $user->id,
            'work_email' => $data['work_email'],
            'personal_email' => $data['personal_email'] ?? null,
            'region_id' => $data['region_id'] ?? null,
            'directorate_id' => $data['directorate_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'designation' => $data['designation'] ?? null,
            'employment_type' => $data['employment_type'] ?? null,
        ]);

        $user->assignRole('staff');

        $this->awardPoints($user, 'first_login');
        $this->createAuditLog($user, 'staff_onboarding_completed', request(), Staff::class, $staff->id);
    }

    private function awardPoints(User $user, string $event): void
    {
        $config = PointsConfiguration::where('event', $event)->first();
        if ($config) {
            Point::create([
                'user_id' => $user->id,
                'amount' => $config->points,
                'reason' => 'First login',
                'awarded_by' => null,
                'awarded_at' => now(),
            ]);
        }
    }

    private function createAuditLog(User $user, string $event, Request $request, ?string $modelType = null, ?int $modelId = null): void
    {
        Audit::create([
            'user_id' => $user->id,
            'event' => $event,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}