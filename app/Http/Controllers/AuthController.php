<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCodeMail;
use App\Services\ActivityLogService;
use App\Services\CaptchaService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    protected SupabaseService $supabase;
    protected CaptchaService $captcha;
    protected ActivityLogService $activityLog;

    public function __construct(SupabaseService $supabase, CaptchaService $captcha, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->captcha = $captcha;
        $this->activityLog = $activityLog;
    }

    public function showLogin()
    {
        if (Session::has('user_id') && Session::get('2fa_verified')) {
            return redirect()->route('dashboard');
        }

        $captchaImage = $this->captcha->create();
        return view('auth.login', compact('captchaImage'));
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'captcha' => 'required|string',
        ]);

        // Verify captcha
        if (!$this->captcha->verify($request->captcha)) {
            return back()->withErrors(['captcha' => 'Kode captcha tidak valid.'])->withInput();
        }

        // Rate limiting check (via Cache, tied to IP+email — not bypassable by clearing cookies)
        $rateLimitKey = 'login_attempts:' . md5($request->ip() . '|' . $request->email);
        $lockKey = 'login_locked:' . md5($request->ip() . '|' . $request->email);
        $attempts = (int) Cache::get($rateLimitKey, 0);

        if (Cache::has($lockKey)) {
            $lockedUntil = Cache::get($lockKey);
            $minutes = max(1, now()->diffInMinutes($lockedUntil));
            return back()->withErrors([
                'email' => "Akun terkunci. Coba lagi dalam {$minutes} menit.",
            ])->withInput();
        }

        // Attempt login via Supabase
        $result = $this->supabase->signIn($request->email, $request->password);

        if (!$result['success']) {
            // Increment failed attempts in cache (persists across sessions)
            $attempts++;
            Cache::put($rateLimitKey, $attempts, now()->addMinutes(15));

            if ($attempts >= 5) {
                Cache::put($lockKey, now()->addMinutes(15), now()->addMinutes(15));
                return back()->withErrors([
                    'email' => 'Terlalu banyak percobaan gagal. Akun dikunci selama 15 menit.',
                ])->withInput();
            }

            return back()->withErrors([
                'email' => 'Email atau password salah.',
            ])->withInput();
        }

        $authData = $result['data'];
        $userId = $authData['user']['id'];
        $accessToken = $authData['access_token'];
        $refreshToken = $authData['refresh_token'];

        // Get profile from Supabase using service key (bypasses RLS infinite recursion)
        $profiles = $this->supabase->selectAdmin('profiles', '*', ['id' => $userId]);
        $profile = !empty($profiles) ? $profiles[0] : null;

        if (!$profile) {
            $this->supabase->signOut($accessToken);
            return back()->withErrors(['email' => 'Profil tidak ditemukan. Hubungi administrator.'])->withInput();
        }

        // Check if account is active
        if (!$profile['is_active']) {
            $this->supabase->signOut($accessToken);
            return back()->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.'])->withInput();
        }

        // Check if account is locked
        if (isset($profile['locked_until']) && $profile['locked_until']) {
            if (now()->lt($profile['locked_until'])) {
                $this->supabase->signOut($accessToken);
                return back()->withErrors(['email' => 'Akun terkunci. Coba lagi nanti.'])->withInput();
            }
        }

        // Reset failed attempts
        $this->supabase->update('profiles', ['id' => $userId], [
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now()->toIso8601String(),
            'last_login_ip' => $request->ip(),
        ], true);

        // Clear rate limiting on successful login
        Cache::forget($rateLimitKey);
        Cache::forget($lockKey);

        // Regenerate session ID to prevent session fixation attacks
        Session::regenerate();

        // Store session data
        Session::put('user_id', $userId);
        Session::put('user_name', $profile['full_name']);
        Session::put('user_email', $request->email);
        Session::put('user_role', $profile['role']);
        Session::put('user_department_id', $profile['department_id']);
        Session::put('supabase_access_token', $accessToken);
        Session::put('supabase_refresh_token', $refreshToken);

        $photoUrl = null;
        if (!empty($profile['profile_photo_url'])) {
            $bucket = config('services.supabase.storage_bucket');
            $photoUrl = $this->supabase->getSignedUrl($bucket, $profile['profile_photo_url'], 604800);
        }
        Session::put('profile_photo_url', $photoUrl);

        // Always require 2FA for all accounts
        Session::put('2fa_required', true);
        Session::put('2fa_verified', false);

        $this->generateAndSend2FACode($userId, $request->email, $profile['full_name']);

        $this->activityLog->log('login', 'Login (menunggu verifikasi 2FA)', null, null, $userId);
        return redirect()->route('2fa.show');
    }

    public function logout(Request $request)
    {
        $userId = Session::get('user_id');
        $accessToken = Session::get('supabase_access_token');

        if ($userId && $accessToken) {
            $this->activityLog->log('logout', 'Logout', null, null, $userId);
            $this->supabase->signOut($accessToken);
        }

        Session::flush();
        return redirect()->route('login')->withCookie(cookie()->forget('remember_me'));
    }

    /**
     * Redirect to OAuth provider (github, google)
     */
    public function oauthRedirect(Request $request, string $provider)
    {
        if (!in_array($provider, ['github', 'google'])) {
            abort(404);
        }

        $redirectTo = config('app.url') . '/oauth-callback';
        $params = http_build_query([
            'provider' => $provider,
            'redirect_to' => $redirectTo,
        ]);
        $oauthUrl = rtrim(config('services.supabase.url'), '/') . '/auth/v1/authorize?' . $params;

        return redirect($oauthUrl);
    }

    /**
     * OAuth callback page (Supabase redirects here with tokens in URL hash)
     */
    public function oauthCallback()
    {
        return view('auth.oauth-callback');
    }

    /**
     * Handle OAuth access token (called via AJAX from callback page)
     */
    public function oauthHandle(Request $request)
    {
        $request->validate([
            'access_token' => 'required|string',
            'refresh_token' => 'required|string',
        ]);

        $accessToken = $request->access_token;
        $refreshToken = $request->refresh_token;

        $user = $this->supabase->verifyAccessToken($accessToken);
        if (!$user) {
            return response()->json(['success' => false, 'error' => 'Token OAuth tidak valid.'], 401);
        }

        $userId = $user['id'];

        $profiles = $this->supabase->selectAdmin('profiles', '*', ['id' => $userId]);
        $profile = !empty($profiles) ? $profiles[0] : null;

        if (!$profile) {
            $email = $user['email'] ?? '';
            $fullName = $user['user_metadata']['full_name']
                ?? $user['user_metadata']['name']
                ?? explode('@', $email)[0];

            $profileData = [
                'phone' => '',
                'role' => 'karyawan',
                'two_factor_enabled' => false,
                'is_active' => true,
            ];

            $profileResult = $this->supabase->update('profiles', ['id' => $userId], $profileData, true);

            if (!$profileResult['success']) {
                $profileData['id'] = $userId;
                $profileData['full_name'] = $fullName;
                $profileResult = $this->supabase->insert('profiles', $profileData, true);
            }

            if ($profileResult['success']) {
                $profiles = $this->supabase->selectAdmin('profiles', '*', ['id' => $userId]);
                $profile = !empty($profiles) ? $profiles[0] : $profileData;
                $this->supabase->update('leave_balances', ['user_id' => $userId, 'tahun' => date('Y')], [
                    'total_jatah' => 12,
                    'terpakai' => 0,
                    'sisa' => 12,
                ], true);
            } else {
                return response()->json(['success' => false, 'error' => 'Profil belum dibuat. Hubungi administrator.'], 403);
            }
        }

        if (!$profile['is_active']) {
            return response()->json(['success' => false, 'error' => 'Akun Anda tidak aktif. Hubungi administrator.'], 403);
        }

        $this->supabase->update('profiles', ['id' => $userId], [
            'last_login_at' => now()->toIso8601String(),
            'last_login_ip' => $request->ip(),
        ], true);

        Session::regenerate();
        Session::put('user_id', $userId);
        Session::put('user_name', $profile['full_name']);
        Session::put('user_email', $user['email'] ?? '');
        Session::put('user_role', $profile['role']);
        Session::put('user_department_id', $profile['department_id'] ?? null);
        Session::put('supabase_access_token', $accessToken);
        Session::put('supabase_refresh_token', $refreshToken);

        $photoUrl = null;
        if (!empty($profile['profile_photo_url'])) {
            $bucket = config('services.supabase.storage_bucket');
            $photoUrl = $this->supabase->getSignedUrl($bucket, $profile['profile_photo_url'], 604800);
        }
        Session::put('profile_photo_url', $photoUrl);

        // Always require 2FA for all accounts
        Session::put('2fa_required', true);
        Session::put('2fa_verified', false);

        $this->generateAndSend2FACode($userId, $user['email'] ?? '', $profile['full_name']);

        $this->activityLog->log('login', 'Login via OAuth (menunggu verifikasi 2FA)', null, null, $userId);
        return response()->json(['success' => true, 'redirect' => route('2fa.show')]);
    }

    /**
     * Generate and send 2FA code
     */
    protected function generateAndSend2FACode(string $userId, string $email, string $userName): void
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->supabase->update('two_factor_codes', ['user_id' => $userId, 'used' => 'false'], [
            'used' => true,
        ], true);

        $this->supabase->insert('two_factor_codes', [
            'user_id' => $userId,
            'kode' => $code,
            'used' => false,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ], true);

        try {
            Mail::to($email)->send(new TwoFactorCodeMail($code, $userName));
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA email: ' . $e->getMessage());
        }
    }
}
