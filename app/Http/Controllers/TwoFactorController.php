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

    // Menginisialisasi class dan dependensi
    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    // Menampilkan detail dari data spesifik
    public function show()
    {
        if (!Session::get('2fa_required')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor');
    }

    // Fungsi untuk menangani proses verify
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

        // Mengecek kode
        $codes = $this->supabase->selectAdmin('two_factor_codes', '*', [
            'user_id' => $userId,
            'used' => 'false',
        ]);

        Log::info('2FA verify: codes found', [
            'user_id' => $userId,
            'count' => count($codes),
            'input_code_prefix' => substr($request->code, 0, 2) . '****',
        ]);

        $validCode = null;
        $expiredCount = 0;

        foreach ($codes as $code) {
            $expiresAt = strtotime($code['expires_at']);
            $now = time();

            if ($expiresAt <= $now) {
                $expiredCount++;
                continue;
            }

            // Use hash_equals() to prevent timing attacks
            if (hash_equals($code['kode'], $request->code)) {
                $validCode = $code;
                break;
            }
        }

        if (!$validCode) {
            Log::warning('2FA verify failed', [
                'user_id' => $userId,
                'total_unused_codes' => count($codes),
                'expired_count' => $expiredCount,
                'input_code_prefix' => substr($request->code, 0, 2) . '****',
            ]);

            $attempts++;
            Cache::put($rateKey, $attempts, now()->addMinutes(15));

            if ($attempts >= 5) {
                Cache::put($lockKey, now()->addMinutes(15), now()->addMinutes(15));
                return back()->withErrors(['code' => 'Terlalu banyak percobaan gagal. Kode dikunci selama 15 menit.']);
            }

            // Give user a more helpful error message
            if (count($codes) === 0) {
                return back()->withErrors(['code' => 'Kode tidak ditemukan. Silakan klik "Kirim Ulang Kode" untuk mendapatkan kode baru.']);
            }

            if ($expiredCount === count($codes)) {
                return back()->withErrors(['code' => 'Kode sudah kadaluarsa. Silakan klik "Kirim Ulang Kode" untuk mendapatkan kode baru.']);
            }

            return back()->withErrors(['code' => 'Kode yang Anda masukkan salah. Periksa kembali 6 digit kode dari email.']);
        }

        Cache::forget($rateKey);
        Cache::forget($lockKey);

        // Mark code as used
        $this->supabase->update('two_factor_codes', ['id' => $validCode['id']], [
            'used' => true,
        ], true);

        // Menyimpan sesi 2FA
        Session::put('2fa_verified', true);
        Session::put('2fa_verified_at', now()->toIso8601String());

        $this->activityLog->log('2fa_verify', 'Verifikasi 2FA berhasil');

        return redirect()->route('dashboard');
    }

    // Fungsi untuk menangani proses resend
    public function resend()
    {
        $userId = Session::get('user_id');
        $rateKey = '2fa_resend:' . md5($userId);
        $resendCount = (int) Cache::get($rateKey, 0);

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
            'expires_at' => now()->addMinutes(10)->toIso8601String(),
        ], true);

        try {
            $email = Session::get('user_email', '');
            $userName = Session::get('user_name', '');
            retry(2, function () use ($email, $code, $userName) {
                Mail::to($email)->send(new TwoFactorCodeMail($code, $userName));
            }, 1000);
        } catch (\Exception $e) {
            Log::error('Failed to send 2FA resend email: ' . $e->getMessage());
        }

        Cache::put($rateKey, $resendCount + 1, now()->addHours(1));

        return back()->with('success', 'Kode verifikasi baru telah dikirim ke email Anda.');
    }
}
   