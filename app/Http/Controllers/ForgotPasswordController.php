<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ForgotPasswordController extends Controller
{
    protected SupabaseService $supabase;
    protected ActivityLogService $activityLog;

    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $rateKey = 'password_reset:' . md5($request->ip());
        $attempts = (int) Cache::get($rateKey, 0);

        if ($attempts >= 3) {
            return back()->withErrors([
                'email' => 'Terlalu banyak permintaan reset password. Coba lagi dalam 15 menit.',
            ])->withInput();
        }

        $redirectTo = config('app.url') . '/reset-password';

        $result = $this->supabase->resetPasswordEmail($request->email, $redirectTo);

        if ($result['success']) {
            Cache::put($rateKey, $attempts + 1, now()->addMinutes(15));
            Log::info('Reset password link sent to: ' . $request->email);
            $this->activityLog->log('forgot_password', "Permintaan reset password untuk email: {$request->email}");
        } else {
            Log::info('Reset password requested for non-existent email: ' . $request->email);
        }

        return back()->with('success', 'Jika email terdaftar, link reset password telah dikirim. Silakan cek inbox atau folder spam.');
    }

    public function showResetPassword(Request $request)
    {
        // Supabase redirects here with ?token=xxx&type=recovery
        $token = $request->query('token', '');
        $type  = $request->query('type', 'recovery');
        $isAccessToken = $request->query('is_access_token', false);

        // If no token, show error
        if (empty($token)) {
            return redirect()->route('forgot-password')
                ->withErrors(['email' => 'Link reset tidak valid atau sudah kadaluarsa.']);
        }

        return view('auth.reset-password', compact('token', 'type', 'isAccessToken'));
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
            'token'    => 'required|string',
            'is_access_token' => 'nullable|boolean',
        ], [
            'password.regex' => 'Password harus mengandung huruf besar, huruf kecil, angka, dan karakter spesial.',
        ]);

        if ($request->is_access_token) {
            $accessToken = $request->token;
            // Verify access token is valid
            $user = $this->supabase->getUser($accessToken);
            if (!$user) {
                Log::warning('Reset password access token verification failed');
                return back()->withErrors([
                    'password' => 'Token tidak valid atau sudah kadaluarsa. Silakan minta link baru.',
                ]);
            }
            $userEmail = $user['email'] ?? 'unknown';
            $userId = $user['id'] ?? null;
        } else {
            // Step 1: Verify the recovery token with Supabase
            // This exchanges the recovery token for a valid access_token
            $verifyResult = $this->supabase->verifyOtp($request->token, 'recovery');

            if (!$verifyResult['success']) {
                Log::warning('Reset password OTP verification failed: ' . ($verifyResult['error'] ?? 'unknown'));
                return back()->withErrors([
                    'password' => 'Token tidak valid atau sudah kadaluarsa. Silakan minta link baru.',
                ]);
            }

            // Step 2: Extract the access_token from the verify response
            $accessToken = $verifyResult['data']['access_token'] ?? null;
            if (!$accessToken) {
                return back()->withErrors(['password' => 'Gagal memverifikasi token. Silakan coba lagi.']);
            }
            $userEmail = $verifyResult['data']['user']['email'] ?? 'unknown';
            $userId = $verifyResult['data']['user']['id'] ?? null;
        }

        // Step 3: Update the password using the valid access_token
        $updateResult = $this->supabase->updatePassword($request->password, $accessToken);

        if ($updateResult['success']) {
            Log::info('Password reset successful for user: ' . $userEmail);
            $this->activityLog->log('reset_password', 'Password berhasil direset', null, null, $userId);
            return redirect()->route('login')
                ->with('success', 'Password berhasil direset! Silakan login dengan password baru.');
        }

        return back()->withErrors([
            'password' => 'Gagal mereset password: ' . ($updateResult['error'] ?? 'Terjadi kesalahan.'),
        ]);
    }
}
