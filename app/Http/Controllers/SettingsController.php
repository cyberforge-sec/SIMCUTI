<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    protected SupabaseService $supabase;
    protected ActivityLogService $activityLog;

    // Menginisialisasi class dan dependensi
    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    // Menampilkan halaman utama atau daftar data
    public function index()
    {
        $userId = Session::get('user_id');
        $profile = $this->supabase->selectSingle('profiles', 'id', $userId);

        return view('settings.index', compact('profile'));
    }

    // Fungsi untuk menangani proses toggle2FA
    public function toggle2FA(Request $request)
    {
        $userId = Session::get('user_id');
        $profile = $this->supabase->selectSingle('profiles', 'id', $userId);

        if (!$profile) {
            return back()->withErrors(['error' => 'Profil tidak ditemukan.']);
        }

        $newStatus = !$profile['two_factor_enabled'];

        $result = $this->supabase->update('profiles', ['id' => $userId], [
            'two_factor_enabled' => $newStatus,
        ], true);

        if ($result['success']) {
            $action = $newStatus ? 'mengaktifkan' : 'menonaktifkan';
            $this->activityLog->log('update', "{$action} autentikasi dua faktor (2FA)");
            return back()->with('success', "2FA berhasil {$action}.");
        }

        return back()->withErrors(['error' => 'Gagal mengubah pengaturan 2FA.']);
    }

    // Fungsi untuk menangani proses changePassword
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[^A-Za-z0-9]/',
            ],
        ], [
            'new_password.regex' => 'Password harus mengandung huruf besar, huruf kecil, angka, dan karakter spesial.',
        ]);

        $email = Session::get('user_email');
        $currentPassword = $request->current_password;

        // Verify current password by attempting to sign in
        $signInResult = $this->supabase->signIn($email, $currentPassword);
        if (!$signInResult['success']) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        // Memperbarui password
        $accessToken = $signInResult['data']['access_token'];
        $result = $this->supabase->updatePassword($request->new_password, $accessToken);

        if ($result['success']) {
            $this->activityLog->log('update', 'Mengubah password');

            // Invalidate current session tokens and regenerate session
            // to prevent use of old access tokens after password change
            $oldAccessToken = Session::get('supabase_access_token');
            if ($oldAccessToken) {
                $this->supabase->signOut($oldAccessToken);
            }

            // Store new tokens from the password change response
            // (Supabase returns new tokens after password update)
            if (isset($result['data']['access_token'])) {
                Session::put('supabase_access_token', $result['data']['access_token']);
            }
            if (isset($result['data']['refresh_token'])) {
                Session::put('supabase_refresh_token', $result['data']['refresh_token']);
            }

            Session::regenerate();

            return back()->with('success', 'Password berhasil diubah.');
        }

        return back()->withErrors(['error' => 'Gagal mengubah password: ' . ($result['error'] ?? '')]);
    }
}
   