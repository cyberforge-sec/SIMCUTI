<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class UserController extends Controller
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
        $users = $this->supabase->selectAdvanced('profiles', [
            'columns' => '*',
            'order' => 'full_name.asc',
        ]);

        // Enrich with department names
        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        $deptMap = [];
        foreach ($departments as $d) {
            $deptMap[$d['id']] = $d['nama'];
        }

        foreach ($users as &$user) {
            $user['department_name'] = $deptMap[$user['department_id'] ?? ''] ?? '-';
        }

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        return view('users.form', compact('departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,manager,karyawan',
            'department_id' => 'nullable|string',
            'jatah_cuti_tahunan' => 'integer|min:0|max:365',
        ]);

        // Create auth user via Supabase Admin API
        $authResult = $this->supabase->adminCreateUser(
            $request->email,
            $request->password,
            ['user_metadata' => ['full_name' => $request->full_name]]
        );

        if (!$authResult['success']) {
            return back()->withErrors(['email' => 'Gagal membuat user: ' . ($authResult['error'] ?? 'Unknown error')])->withInput();
        }

        $userId = $authResult['data']['id'];

        // Update profile (handle_new_user trigger already created it with basic data)
        $profileData = [
            'phone' => $request->phone,
            'role' => $request->role,
            'department_id' => $request->department_id ?: null,
            'jatah_cuti_tahunan' => $request->jatah_cuti_tahunan ?? 12,
            'sisa_cuti' => $request->jatah_cuti_tahunan ?? 12,
            'two_factor_enabled' => in_array($request->role, ['admin', 'manager']),
            'is_active' => true,
        ];

        $profileResult = $this->supabase->update('profiles', ['id' => $userId], $profileData, true);

        if (!$profileResult['success']) {
            // Trigger may not have fired; try insert as fallback
            $profileData['id'] = $userId;
            $profileData['full_name'] = $request->full_name;
            $profileResult = $this->supabase->insert('profiles', $profileData, true);

            if (!$profileResult['success']) {
                $this->supabase->adminDeleteUser($userId);
                return back()->withErrors(['error' => 'Gagal membuat profil: ' . ($profileResult['error'] ?? 'Unknown')])->withInput();
            }
        }

        // Update leave balance (trigger already created it, ensure correct values)
        $this->supabase->update('leave_balances', ['user_id' => $userId, 'tahun' => date('Y')], [
            'total_jatah' => $request->jatah_cuti_tahunan ?? 12,
            'terpakai' => 0,
            'sisa' => $request->jatah_cuti_tahunan ?? 12,
        ], true);

        $this->activityLog->log('create', "Membuat user: {$request->full_name} ({$request->email})", 'profile', $userId);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $user = $this->supabase->selectSingle('profiles', 'id', $id);
        if (!$user) {
            return redirect()->route('users.index')->withErrors(['error' => 'User tidak ditemukan.']);
        }

        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        return view('users.form', compact('user', 'departments'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,manager,karyawan',
            'department_id' => 'nullable|string',
            'jatah_cuti_tahunan' => 'integer|min:0|max:365',
        ]);

        $data = [
            'full_name' => $request->full_name,
            'phone' => $request->phone,
            'role' => $request->role,
            'department_id' => $request->department_id ?: null,
            'jatah_cuti_tahunan' => $request->jatah_cuti_tahunan ?? 12,
        ];

        $result = $this->supabase->update('profiles', ['id' => $id], $data, true);

        if ($result['success']) {
            $this->activityLog->log('update', "Memperbarui user: {$request->full_name}", 'profile', $id);
            return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal memperbarui user.'])->withInput();
    }

    public function toggleActive(string $id)
    {
        $user = $this->supabase->selectSingle('profiles', 'id', $id);
        if (!$user) {
            return back()->withErrors(['error' => 'User tidak ditemukan.']);
        }

        $newStatus = !$user['is_active'];
        $this->supabase->update('profiles', ['id' => $id], ['is_active' => $newStatus], true);

        $action = $newStatus ? 'mengaktifkan' : 'menonaktifkan';
        $this->activityLog->log('update', "{$action} user: {$user['full_name']}", 'profile', $id);

        return redirect()->route('users.index')->with('success', "User berhasil {$action}.");
    }

    public function destroy(string $id)
    {
        // Soft delete: deactivate instead of delete
        $this->supabase->update('profiles', ['id' => $id], ['is_active' => false], true);

        $this->activityLog->log('delete', 'Menghapus user', 'profile', $id);
        return redirect()->route('users.index')->with('success', 'User berhasil dinonaktifkan.');
    }
}
