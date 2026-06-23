<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
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
        $departments = $this->supabase->selectAdvanced('departments', [
            'columns' => '*',
            'order' => 'nama.asc',
            'filters' => ['is_active' => 'eq.true'],
        ]);

        // Get managers for each department
        foreach ($departments as &$dept) {
            if (!empty($dept['manager_id'])) {
                $managers = $this->supabase->select('profiles', 'full_name', ['id' => $dept['manager_id']]);
                $dept['manager_name'] = !empty($managers) ? $managers[0]['full_name'] : '-';
            } else {
                $dept['manager_name'] = '-';
            }
        }

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        // Get available managers (role = manager, not already assigned)
        $managers = $this->supabase->select('profiles', 'id,full_name', [
            'role' => 'manager',
            'is_active' => 'true',
        ]);

        return view('departments.form', compact('managers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => 'required|string|max:20',
            'manager_id' => 'nullable|string',
            'deskripsi' => 'nullable|string',
        ]);

        // Manual uniqueness check via Supabase (can't use Laravel's unique rule with REST API)
        $existing = $this->supabase->selectAdvanced('departments', [
            'columns' => 'id',
            'filters' => ['kode' => 'eq.' . $request->kode, 'is_active' => 'eq.true'],
        ]);
        if (!empty($existing)) {
            return back()->withErrors(['kode' => 'Kode departemen sudah digunakan.'])->withInput();
        }

        // Validate that manager_id belongs to a user with role='manager'
        if ($request->manager_id) {
            $managerProfile = $this->supabase->selectAdmin('profiles', 'role', ['id' => $request->manager_id]);
            if (empty($managerProfile) || $managerProfile[0]['role'] !== 'manager') {
                return back()->withErrors(['manager_id' => 'User yang dipilih bukan seorang manager.'])->withInput();
            }
        }

        $data = [
            'nama' => $request->nama,
            'kode' => $request->kode,
            'deskripsi' => $request->deskripsi,
            'is_active' => true,
        ];

        if ($request->manager_id) {
            $data['manager_id'] = $request->manager_id;
        }

        $result = $this->supabase->insert('departments', $data, true);

        if ($result['success']) {
            $this->activityLog->log('create', "Membuat departemen: {$request->nama}", 'department', $result['data'][0]['id'] ?? null);
            return redirect()->route('departments.index')->with('success', 'Departemen berhasil ditambahkan.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal menambahkan departemen.'])->withInput();
    }

    public function edit(string $id)
    {
        $department = $this->supabase->selectSingle('departments', 'id', $id);
        if (!$department) {
            return redirect()->route('departments.index')->withErrors(['error' => 'Departemen tidak ditemukan.']);
        }

        $managers = $this->supabase->select('profiles', 'id,full_name', [
            'role' => 'manager',
            'is_active' => 'true',
        ]);

        return view('departments.form', compact('department', 'managers'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => 'required|string|max:20',
            'manager_id' => 'nullable|string',
            'deskripsi' => 'nullable|string',
        ]);

        // Validate that manager_id belongs to a user with role='manager'
        if ($request->manager_id) {
            $managerProfile = $this->supabase->selectAdmin('profiles', 'role', ['id' => $request->manager_id]);
            if (empty($managerProfile) || $managerProfile[0]['role'] !== 'manager') {
                return back()->withErrors(['manager_id' => 'User yang dipilih bukan seorang manager.'])->withInput();
            }
        }

        $data = [
            'nama' => $request->nama,
            'kode' => $request->kode,
            'deskripsi' => $request->deskripsi,
            'manager_id' => $request->manager_id ?: null,
        ];

        $result = $this->supabase->update('departments', ['id' => $id], $data, true);

        if ($result['success']) {
            $this->activityLog->log('update', "Memperbarui departemen: {$request->nama}", 'department', $id);
            return redirect()->route('departments.index')->with('success', 'Departemen berhasil diperbarui.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal memperbarui departemen.'])->withInput();
    }

    public function destroy(string $id)
    {
        $result = $this->supabase->update('departments', ['id' => $id], [
            'is_active' => false,
        ], true);

        if ($result['success']) {
            $this->activityLog->log('delete', 'Menghapus departemen', 'department', $id);
            return redirect()->route('departments.index')->with('success', 'Departemen berhasil dihapus.');
        }

        return back()->withErrors(['error' => 'Gagal menghapus departemen.']);
    }
}
