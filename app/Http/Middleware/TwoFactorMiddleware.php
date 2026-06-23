<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorMiddleware
{
    /**
     * Maximum age (in minutes) for a 2FA verification before requiring re-verification.
     */
    protected const TWO_FA_MAX_AGE_MINUTES = 30;

    public function handle(Request $request, Closure $next): Response
    {
        // If user has 2FA enabled but hasn't verified yet, redirect to 2FA page
        if (Session::get('2fa_required') && !Session::get('2fa_verified')) {
            // Allow access to 2FA verification routes
            if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('2fa.show');
        }

        // Server-side validation: check 2FA verification timestamp
        // Prevents session manipulation where attacker sets 2fa_verified=true
        if (Session::get('2fa_verified')) {
            $verifiedAt = Session::get('2fa_verified_at');

            // If no timestamp exists but 2fa_verified is true, session may be tampered
            if (!$verifiedAt) {
                Session::put('2fa_verified', false);
                if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
                    return $next($request);
                }
                return redirect()->route('2fa.show');
            }

            // Check if 2FA verification has expired
            $minutesSinceVerification = now()->diffInMinutes($verifiedAt);
            if ($minutesSinceVerification > self::TWO_FA_MAX_AGE_MINUTES) {
                Session::put('2fa_verified', false);
                Session::forget('2fa_verified_at');
                if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
                    return $next($request);
                }
                return redirect()->route('2fa.show')
                    ->withErrors(['code' => 'Sesi verifikasi 2FA telah berakhir. Silakan verifikasi ulang.']);
            }
        }

        return $next($request);
    }
}
