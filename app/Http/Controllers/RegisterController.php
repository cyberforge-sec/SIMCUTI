<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\CaptchaService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class RegisterController extends Controller
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

    public function showRegister()
    {
        if (Session::has('user_id')) {
            return redirect()->route('dashboard');
        }

        $captchaImage = $this->captcha->create();
        return view('auth.register', compact('captchaImage'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'captcha' => 'required|string',
            'terms' => 'accepted',
        ], [
            'password.confirmed' => 'Password dan konfirmasi password tidak cocok.',
            'password.min' => 'Password minimal 8 karakter.',
            'terms.accepted' => 'Anda harus menyetujui syarat dan ketentuan.',
        ]);

        // Verify captcha
        if (!$this->captcha->verify($request->captcha)) {
            return back()->withErrors(['captcha' => 'Kode captcha tidak valid.'])->withInput();
        }

        // Password strength check (uppercase, lowercase, digit, special char)
        $password = $request->password;
        if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            return back()->withErrors(['password' => 'Password harus mengandung huruf besar, huruf kecil, angka, dan karakter spesial.'])->withInput();
        }

        // Register via Supabase Auth
        $result = $this->supabase->signUp($request->email, $request->password, [
            'full_name' => $request->full_name,
            'phone' => $request->phone,
        ]);

        if (!$result['success']) {
            $error = $result['error'];
            if (str_contains(strtolower($error), 'already')) {
                return back()->withErrors(['email' => 'Email sudah terdaftar.'])->withInput();
            }
            return back()->withErrors(['email' => 'Registrasi gagal: ' . $error])->withInput();
        }

        $authData = $result['data'];
        $userId = $authData['user']['id'] ?? null;

        if (!$userId) {
            return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan cek email untuk verifikasi, lalu login.');
        }

        // Update profile record (handle_new_user trigger already created it with basic data)
        $profileData = [
            'phone' => $request->phone,
            'role' => 'karyawan',
            'jatah_cuti_tahunan' => 12,
            'sisa_cuti' => 12,
            'two_factor_enabled' => false,
            'is_active' => true,
        ];

        $profileResult = $this->supabase->update('profiles', ['id' => $userId], $profileData, true);

        if (!$profileResult['success']) {
            // Trigger may not have fired yet; try insert as fallback
            $profileData['id'] = $userId;
            $profileData['full_name'] = $request->full_name;
            $profileResult = $this->supabase->insert('profiles', $profileData, true);

            if (!$profileResult['success']) {
                Log::error('Profile creation failed during register: ' . ($profileResult['error'] ?? 'unknown'));
            }
        }

        // Update leave balance (trigger already created it, just ensure correct values)
        $this->supabase->update('leave_balances', ['user_id' => $userId, 'tahun' => date('Y')], [
            'total_jatah' => 12,
            'terpakai' => 0,
            'sisa' => 12,
        ], true);

        $this->activityLog->log('register', "User baru terdaftar: {$request->email}", 'profile', $userId, $userId);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan cek email untuk verifikasi, lalu login.');
    }
}
