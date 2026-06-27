<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class DepartmentController extends Controller
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
        $departments = $this->supabase->selectAdvanced('departments', [
            'columns' => '*',
            'order' => 'nama.asc',
            'filters' => ['is_active' => 'eq.true'],
        ]);

        // Mengambil data manajer departemen
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

    // Menampilkan form untuk membuat data baru
    public function create()
    {
        // Mengambil manajer yang tersedia
        $managers = $this->supabase->select('profiles', 'id,full_name', [
            'role' => 'manager',
            'is_active' => 'true',
        ]);

        return view('departments.form', compact('managers'));
    }

    // Memproses dan menyimpan data baru ke database
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

        // Validasi manajer
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

    // Menampilkan form untuk mengubah data
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

    // Memproses dan memperbarui data di database
    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => 'required|string|max:20',
            'manager_id' => 'nullable|string',
            'deskripsi' => 'nullable|string',
        ]);

        // Validasi manajer
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

    // Menghapus data dari database
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
