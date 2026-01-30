<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class OtpController extends Controller
{
    public function __construct(
        private OtpService $otpService
    ) {}

    public function showLoginForm()
    {
        // If user has an active OTP session, redirect to OTP verification
        if (session()->has('otp_email')) {
            $remainingSeconds = 60;
            if (session()->has('otp_countdown_start')) {
                $elapsed = now()->timestamp - session('otp_countdown_start');
                $remainingSeconds = max(0, 60 - $elapsed);
            }

            return Inertia::render('auth/otp-verify', [
                'email' => session('otp_email'),
                'remainingSeconds' => $remainingSeconds
            ]);
        }
        return Inertia::render('auth/login');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;

        if ($this->otpService->isRateLimited($email)) {
            return response()->json(['message' => 'Too many OTP requests. Please try again later.'], 429);
        }

        $this->otpService->generateAndSendOtp($email);
        $this->otpService->recordRateLimitAttempt($email);

        // Clear verification rate limit when sending new OTP
        $this->otpService->clearVerificationRateLimit($email);

        // Store email in session for persistence across page refreshes
        session(['otp_email' => $email]);
        // Store countdown start time for persistence across refreshes
        session(['otp_countdown_start' => now()->timestamp]);

        return Inertia::render('auth/otp-verify', [
            'email' => $email,
            'remainingSeconds' => 60
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $email = $request->email;

        // Check if user is rate limited for OTP verification attempts
        if ($this->otpService->isVerificationRateLimited($email)) {
            return back()->withErrors([
                'otp' => 'Too many failed verification attempts. Please try again later.'
            ]);
        }

        $otpRecord = $this->otpService->validateOtp($email, $request->otp);

        if (!$otpRecord) {
            // Record failed verification attempt
            $this->otpService->recordVerificationRateLimitAttempt($email);

            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        $this->otpService->markOtpAsUsed($otpRecord);

        // Clear rate limits on successful verification
        $this->otpService->clearVerificationRateLimit($email);

        // Clear OTP session on successful verification
        session()->forget(['otp_email', 'otp_countdown_start']);

        $user = $this->otpService->findOrCreateUser($request->email);

        Auth::login($user);

        // Redirect to dashboard - middleware will handle onboarding check
        return redirect()->intended(route('dashboard'));
    }

    public function resendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $email = $request->email;

        if ($this->otpService->isRateLimited($email)) {
            return response()->json(['message' => 'Too many OTP requests. Please try again later.'], 429);
        }

        $this->otpService->resendOtp($email);
        $this->otpService->recordRateLimitAttempt($email);

        // Update countdown start time for resend
        session(['otp_countdown_start' => now()->timestamp]);

        return response()->json(['message' => 'OTP resent successfully']);
    }
}
