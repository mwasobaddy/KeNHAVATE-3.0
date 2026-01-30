<?php

namespace App\Services;

use App\Models\User;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public function findOrCreateUser(SocialiteUser $googleUser): User
    {
        $user = User::where('email_google_id', $googleUser->id)
            ->orWhere('work_email_google_id', $googleUser->id)
            ->orWhere('email', $googleUser->email)
            ->orWhere('work_email', $googleUser->email)
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'email_google_id' => $googleUser->id,
                'email_verified_at' => now(),
            ]);
        } else {
            // Update if not set
            if (!$user->email_google_id && $user->email === $googleUser->email) {
                $user->update(['email_google_id' => $googleUser->id]);
            }
            if (!$user->work_email_google_id && $user->work_email === $googleUser->email) {
                $user->update(['work_email_google_id' => $googleUser->id]);
            }
        }

        return $user;
    }
}