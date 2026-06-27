<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class UserController extends Controller
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
    public function index(Request $request)
    {
        // Single query — ambil semua data sekaligus
        $allUsers = collect($this->supabase->selectAdvanced('profiles', [
            'columns' => '*',
            'order' => 'full_name.asc',
        ]));

        // Enrich with department names
        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        $deptMap = [];
        foreach ($departments as $d) {
            $deptMap[$d['id']] = $d['nama'];
        }

        $allUsers = $allUsers->map(function ($user) use ($deptMap) {
            $user['department_name'] = $deptMap[$user['department_id'] ?? ''] ?? '-';
            return $user;
        });

        // Stats (dari data keseluruhan, sebelum filter)
        $stats = [
            'total' => $allUsers->count(),
            'active' => $allUsers->where('is_active', true)->count(),
            'inactive' => $allUsers->where('is_active', false)->count(),
        ];

        // Apply filters
        $filtered = $allUsers;

        // Search filter
        if ($search = $request->input('search')) {
            $searchLower = strtolower($search);
            $filtered = $filtered->filter(function ($user) use ($searchLower) {
                return str_contains(strtolower($user['full_name'] ?? ''), $searchLower)
                    || str_contains(strtolower($user['email'] ?? ''), $searchLower);
            });
        }

        // Role filter
        if ($role = $request->input('role')) {
            $filtered = $filtered->where('role', $role);
        }

        // Status filter
        if ($request->has('status') && $request->input('status') !== '') {
            $status = $request->input('status');
            if ($status === 'active') {
                $filtered = $filtered->where('is_active', true);
            } elseif ($status === 'inactive') {
                $filtered = $filtered->where('is_active', false);
            }
        }

        // Manual pagination
        $perPage = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = $filtered->count();
        $items = $filtered->forPage($currentPage, $perPage)->values();

        $paginatedUsers = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('users.index', compact('paginatedUsers', 'stats'));
    }

    // Menampilkan form untuk membuat data baru
    public function create()
    {
        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        return view('users.form', compact('departments'));
    }

    // Memproses dan menyimpan data baru ke database
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

        // Membuat pengguna autentikasi
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

        // Memperbarui sisa cuti
        $this->supabase->update('leave_balances', ['user_id' => $userId, 'tahun' => date('Y')], [
            'total_jatah' => $request->jatah_cuti_tahunan ?? 12,
            'terpakai' => 0,
            'sisa' => $request->jatah_cuti_tahunan ?? 12,
        ], true);

        $this->activityLog->log('create', "Membuat user: {$request->full_name} ({$request->email})", 'profile', $userId);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    // Menampilkan form untuk mengubah data
    public function edit(string $id)
    {
        $user = $this->supabase->selectSingle('profiles', 'id', $id);
        if (!$user) {
            return redirect()->route('users.index')->withErrors(['error' => 'User tidak ditemukan.']);
        }

        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        return view('users.form', compact('user', 'departments'));
    }

    // Memproses dan memperbarui data di database
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

    // Fungsi untuk menangani proses toggleActive
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

    // Menghapus data dari database
    public function destroy(string $id)
    {
        // Soft delete: deactivate instead of delete
        $this->supabase->update('profiles', ['id' => $id], ['is_active' => false], true);

        $this->activityLog->log('delete', 'Menghapus user', 'profile', $id);
        return redirect()->route('users.index')->with('success', 'User berhasil dinonaktifkan.');
    }
}
