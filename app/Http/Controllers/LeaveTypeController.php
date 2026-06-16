<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
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
        $leaveTypes = $this->supabase->selectAdvanced('leave_types', [
            'columns' => '*',
            'order' => 'nama.asc',
            'filters' => ['is_active' => 'eq.true'],
        ]);

        return view('leave-types.index', compact('leaveTypes'));
    }

    public function create()
    {
        return view('leave-types.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => 'required|string|max:10',
            'max_hari_per_pengajuan' => 'required|integer|min:1|max:365',
            'butuh_dokumen' => 'boolean',
            'deskripsi' => 'nullable|string',
        ]);

        $data = [
            'nama' => $request->nama,
            'kode' => $request->kode,
            'max_hari_per_pengajuan' => $request->max_hari_per_pengajuan,
            'butuh_dokumen' => $request->boolean('butuh_dokumen'),
            'deskripsi' => $request->deskripsi,
            'is_active' => true,
        ];

        $result = $this->supabase->insert('leave_types', $data, true);

        if ($result['success']) {
            $this->activityLog->log('create', "Membuat jenis cuti: {$request->nama}", 'leave_type', $result['data'][0]['id'] ?? null);
            return redirect()->route('leave-types.index')->with('success', 'Jenis cuti berhasil ditambahkan.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal menambahkan jenis cuti.'])->withInput();
    }

    public function edit(string $id)
    {
        $leaveType = $this->supabase->selectSingle('leave_types', 'id', $id);
        if (!$leaveType) {
            return redirect()->route('leave-types.index')->withErrors(['error' => 'Jenis cuti tidak ditemukan.']);
        }

        return view('leave-types.form', compact('leaveType'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'nama' => 'required|string|max:100',
            'kode' => 'required|string|max:10',
            'max_hari_per_pengajuan' => 'required|integer|min:1|max:365',
            'butuh_dokumen' => 'boolean',
            'deskripsi' => 'nullable|string',
        ]);

        $data = [
            'nama' => $request->nama,
            'kode' => $request->kode,
            'max_hari_per_pengajuan' => $request->max_hari_per_pengajuan,
            'butuh_dokumen' => $request->boolean('butuh_dokumen'),
            'deskripsi' => $request->deskripsi,
        ];

        $result = $this->supabase->update('leave_types', ['id' => $id], $data, true);

        if ($result['success']) {
            $this->activityLog->log('update', "Memperbarui jenis cuti: {$request->nama}", 'leave_type', $id);
            return redirect()->route('leave-types.index')->with('success', 'Jenis cuti berhasil diperbarui.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal memperbarui jenis cuti.'])->withInput();
    }

    public function destroy(string $id)
    {
        // Check if leave type is used
        $usedCount = $this->supabase->count('leave_requests', ['leave_type_id' => $id], true);
        if ($usedCount > 0) {
            return back()->withErrors(['error' => 'Tidak bisa menghapus jenis cuti yang sudah digunakan.']);
        }

        $result = $this->supabase->update('leave_types', ['id' => $id], [
            'is_active' => false,
        ], true);

        if ($result['success']) {
            $this->activityLog->log('delete', 'Menghapus jenis cuti', 'leave_type', $id);
            return redirect()->route('leave-types.index')->with('success', 'Jenis cuti berhasil dihapus.');
        }

        return back()->withErrors(['error' => 'Gagal menghapus jenis cuti.']);
    }
}
