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

    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    public function index()
    {
        $userId = Session::get('user_id');
        $profile = $this->supabase->selectSingle('profiles', 'id', $userId);

        return view('settings.index', compact('profile'));
    }

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

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $email = Session::get('user_email');
        $currentPassword = $request->current_password;

        // Verify current password by attempting to sign in
        $signInResult = $this->supabase->signIn($email, $currentPassword);
        if (!$signInResult['success']) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        // Update password
        $accessToken = $signInResult['data']['access_token'];
        $result = $this->supabase->updatePassword($request->new_password, $accessToken);

        if ($result['success']) {
            $this->activityLog->log('update', 'Mengubah password');
            return back()->with('success', 'Password berhasil diubah.');
        }

        return back()->withErrors(['error' => 'Gagal mengubah password: ' . ($result['error'] ?? '')]);
    }
}
