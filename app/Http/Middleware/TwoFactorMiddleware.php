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
        // Jika user mengaktifkan 2FA tapi belum diverifikasi, arahkan ke halaman 2FA
        if (Session::get('2fa_required') && !Session::get('2fa_verified')) {
            // Izinkan akses ke halaman rute verifikasi 2FA
            if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
                return $next($request);
            }
            return redirect()->route('2fa.show');
        }

        // Validasi server: periksa waktu verifikasi 2FA
        // Mencegah manipulasi sesi
        if (Session::get('2fa_verified')) {
            $verifiedAt = Session::get('2fa_verified_at');

            // Jika tidak ada data waktu verifikasi, sesi berpotensi dimanipulasi
            if (!$verifiedAt) {
                Session::put('2fa_verified', false);
                if ($request->routeIs('2fa.*') || $request->routeIs('logout')) {
                    return $next($request);
                }
                return redirect()->route('2fa.show');
            }

            // Cek apabila verifikasi 2FA sudah kedaluwarsa
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
