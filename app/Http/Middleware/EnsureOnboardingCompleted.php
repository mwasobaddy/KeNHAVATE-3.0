<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return $next($request);
        }

        // Skip middleware for logout and auth-related routes
        if ($request->routeIs([
            'logout',
            'login', 'login.send', 'otp.verify',
            'google.login', 'google.callback',
            'verification.notice', 'verification.verify', 'verification.send',
            'password.confirm', 'password.confirm.store',
            'two-factor.login', 'two-factor.login.store',
            'user-password.edit', 'user-password.update',
            'profile.edit', 'profile.update', 'profile.destroy',
            'two-factor.show', 'two-factor.enable', 'two-factor.confirm',
            'two-factor.qr-code', 'two-factor.secret-key', 'two-factor.recovery-codes', 'two-factor.regenerate-recovery-codes',
            'appearance.edit',
            'register', 'register.store',
            'password.request', 'password.email', 'password.reset', 'password.update',
            'user.onboarding', 'user.onboarding.store',
            'staff.onboarding', 'staff.onboarding.store'
        ])) {
            return $next($request);
        }

        $user = auth()->user();

        if (!$user->onboarding_completed) {
            // Check if staff based on email domain
            $isStaff = str_ends_with($user->email, '@kenha.co.ke');

            if ($isStaff) {
                return redirect()->route('staff.onboarding');
            } else {
                return redirect()->route('user.onboarding');
            }
        }

        return $next($request);
    }
}
