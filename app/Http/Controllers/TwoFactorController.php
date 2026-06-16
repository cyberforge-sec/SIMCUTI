<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorCodeMail;
use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class TwoFactorController extends Controller
{
    protected SupabaseService $supabase;
    protected ActivityLogService $activityLog;

    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    public function show()
    {
        if (!Session::get('2fa_required')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $userId = Session::get('user_id');
        if (!$userId) {
            return redirect()->route('login');
        }

        $rateKey = '2fa_attempts:' . md5($userId);
        $lockKey = '2fa_locked:' . md5($userId);

        if (Cache::has($lockKey)) {
            $lockedUntil = Cache::get($lockKey);
            $minutes = max(1, now()->diffInMinutes($lockedUntil));
            return back()->withErrors(['code' => "Terlalu banyak percobaan. Coba lagi dalam {$minutes} menit."]);
        }

        $attempts = (int) Cache::get($rateKey, 0);

        // Check code from database (use admin to bypass RLS)
        $codes = $this->supabase->selectAdmin('two_factor_codes', '*', [
            'user_id' => $userId,
            'used' => 'false',
        ]);

        $validCode = null;
        foreach ($codes as $code) {
            if (strtotime($code['expires_at']) <= time()) continue;
            if ($code['kode'] === $request->code) {
                $validCode = $code;
                break;
            }
        }

        if (!$validCode) {
            $attempts++;
            Cache::put($rateKey, $attempts, now()->addMinutes(15));

            if ($attempts >= 5) {
                Cache::put($lockKey, now()->addMinutes(15), now()->addMinutes(15));
                return back()->withErrors(['code' => 'Terlalu banyak percobaan gagal. Kode dikunci selama 15 menit.']);
            }

            return back()->withErrors(['code' => 'Kode verifikasi tidak valid atau sudah kadaluarsa.']);
        }

        Cache::forget($rateKey);
        Cache::forget($lockKey);

        // Mark code as used
        $this->supabase->update('two_factor_codes', ['id' => $validCode['id']], [
            'used' => true,
        ], true);

        // Set session as 2FA verified
        Session::put('2fa_verified', true);

        $this->activityLog->log('2fa_verify', 'Verifikasi 2FA berhasil');

        return redirect()->route('dashboard');
    }

    public function resend()
    {
        $userId = Session::get('user_id');
        $resendCount = Session::get('2fa_resend_count', 0);

        if ($resendCount >= 3) {
            return back()->withErrors(['code' => 'Anda telah mencapai batas pengiriman ulang kode. Silakan login kembali.']);
        }

        // Invalidate old codes
        $this->supabase->update('two_factor_codes', ['user_id' => $userId, 'used' => 'false'], [
            'used' => true,
        ], true);

        // Generate new code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->supabase->insert('two_factor_codes', [
            'user_id' => $userId,
            'kode' => $code,
            'used' => false,
            'expires_at' => now()->addMinutes(5)->toIso8601String(),
        ], true);

        try {
            $email = Session::get('user_email', '');
            $userName = Session::get('user_name', '');
            Mail::to($email)->send(new TwoFactorCodeMail($code, $userName));
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA resend email: ' . $e->getMessage());
        }

        Session::put('2fa_resend_count', $resendCount + 1);

        return back()->with('success', 'Kode verifikasi baru telah dikirim ke email Anda.');
    }
}
