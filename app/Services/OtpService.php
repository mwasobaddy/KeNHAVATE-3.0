<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class OtpService
{
    public function generateAndSendOtp(string $email): void
    {
        $this->invalidatePreviousOtps($email);

        $otp = $this->generateOtpCode();

        Otp::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->sendOtpEmail($email, $otp);
    }

    public function invalidatePreviousOtps(string $email): void
    {
        Otp::where('email', $email)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }

    public function validateOtp(string $email, string $otp): ?Otp
    {
        return Otp::where('email', $email)
            ->where('otp', $otp)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();
    }

    public function markOtpAsUsed(Otp $otp): void
    {
        $otp->update(['used_at' => now()]);
    }

    public function findOrCreateUser(string $email): User
    {
        $user = User::where('email', $email)
            ->orWhere('work_email', $email)
            ->first();

        if (!$user) {
            $user = User::create([
                'email' => $email,
                'email_verified_at' => now(),
            ]);
        }

        return $user;
    }

    public function isRateLimited(string $email): bool
    {
        return RateLimiter::tooManyAttempts('otp:' . $email, 5);
    }

    public function recordRateLimitAttempt(string $email): void
    {
        RateLimiter::hit('otp:' . $email, 60);
    }

    public function isVerificationRateLimited(string $email): bool
    {
        return RateLimiter::tooManyAttempts('otp-verification:' . $email, 5);
    }

    public function recordVerificationRateLimitAttempt(string $email): void
    {
        RateLimiter::hit('otp-verification:' . $email, 900); // 15 minutes lockout
    }

    public function clearVerificationRateLimit(string $email): void
    {
        RateLimiter::clear('otp-verification:' . $email);
    }

    private function generateOtpCode(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function sendOtpEmail(string $email, string $otp): void
    {
        Mail::raw("Your OTP is: $otp", function ($message) use ($email) {
            $message->to($email)->subject('Your OTP Code');
        });
    }

    public function resendOtp(string $email): void
    {
        // Check if there's a valid, unused OTP for this email
        $existingOtp = Otp::where('email', $email)
            ->where('expires_at', '>', now())
            ->whereNull('used_at')
            ->first();

        if ($existingOtp) {
            // Resend the existing OTP
            $this->sendOtpEmail($email, $existingOtp->otp);
        } else {
            // Generate a new OTP if none exists or all are expired
            $this->generateAndSendOtp($email);
        }
    }
}