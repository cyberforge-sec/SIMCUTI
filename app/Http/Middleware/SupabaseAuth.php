<?php

namespace App\Http\Middleware;

use App\Mail\TwoFactorCodeMail;
use App\Services\SupabaseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SupabaseAuth
{
    protected SupabaseService $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_access_token');

        if (!$userId || !$accessToken) {
            if ($this->restoreFromRememberCookie($request)) {
                return $next($request);
            }
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        $tokenExpiry = $this->getTokenExpiry($accessToken);

        if ($tokenExpiry && $tokenExpiry > time() + 60) {
            return $next($request);
        }

        $refreshToken = Session::get('supabase_refresh_token');
        if ($refreshToken && $this->tryRefreshToken($refreshToken)) {
            return $next($request);
        }

        if ($accessToken && $this->supabase->getUser($accessToken)) {
            return $next($request);
        }

        Session::flush();
        return redirect()->route('login')->withErrors(['email' => 'Sesi Anda telah berakhir. Silakan login kembali.']);
    }

    protected function restoreFromRememberCookie(Request $request): bool
    {
        $cookieValue = $request->cookie('remember_me');
        if (!$cookieValue) {
            return false;
        }

        try {
            $data = decrypt($cookieValue);
        } catch (\Exception) {
            return false;
        }

        $refreshToken = $data['refresh_token'] ?? null;
        if (!$refreshToken) {
            return false;
        }

        return $this->tryRefreshToken($refreshToken);
    }

    protected function tryRefreshToken(string $refreshToken): bool
    {
        $refreshed = $this->supabase->refreshToken($refreshToken);
        if (!$refreshed['success']) {
            return false;
        }

        $accessToken = $refreshed['data']['access_token'];
        $user = $this->supabase->getUser($accessToken);
        if (!$user) {
            return false;
        }

        $profiles = $this->supabase->selectAdmin('profiles', '*', ['id' => $user['id']]);
        $profile = !empty($profiles) ? $profiles[0] : null;
        if (!$profile || !$profile['is_active']) {
            return false;
        }

        Session::regenerate();
        Session::put('user_id', $user['id']);
        Session::put('user_name', $profile['full_name']);
        Session::put('user_email', $user['email'] ?? '');
        Session::put('user_role', $profile['role']);
        Session::put('user_department_id', $profile['department_id'] ?? null);
        Session::put('supabase_access_token', $accessToken);
        Session::put('supabase_refresh_token', $refreshed['data']['refresh_token']);

        $photoUrl = null;
        if (!empty($profile['profile_photo_url'])) {
            $bucket = config('services.supabase.storage_bucket');
            $photoUrl = $this->supabase->getSignedUrl($bucket, $profile['profile_photo_url'], 604800);
        }
        Session::put('profile_photo_url', $photoUrl);
        Session::put('2fa_required', true);
        Session::put('2fa_verified', false);

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->supabase->update('two_factor_codes', ['user_id' => $user['id'], 'used' => 'false'], ['used' => true], true);
        $this->supabase->insert('two_factor_codes', [
            'user_id' => $user['id'],
            'kode' => $code,
            'used' => false,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ], true);

        try {
            Mail::to($user['email'] ?? '')->send(new TwoFactorCodeMail($code, $profile['full_name']));
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA email (remember cookie): ' . $e->getMessage());
        }

        return true;
    }

    protected function getTokenExpiry(string $token): ?int
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        return $payload['exp'] ?? null;
    }
}
