<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class LeaveRequestController extends Controller
{
    /**
     * Maximum allowed image dimensions in pixels (width × height).
     * Prevents "image bomb" / memory exhaustion attacks via GD library.
     * 50 megapixels covers all practical use cases (8K = ~33MP).
     */
    protected const MAX_IMAGE_PIXELS = 50_000_000;

    protected SupabaseService $supabase;
    protected ActivityLogService $activityLog;

    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        // Inisialisasi service yang dibutuhkan
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    public function index(Request $request)
    {
        // Ambil ID user dari sesi saat ini
        $userId = Session::get('user_id');

        // Filter data berdasarkan ID user
        $filters = ['user_id' => 'eq.' . $userId];

        // Whitelist valid status values to prevent PostgREST filter injection
        // Tambahkan filter status jika ada request status yang valid
        $allowedStatuses = ['pending', 'disetujui', 'ditolak', 'dibatalkan'];
        if ($request->has('status') && $request->status && in_array($request->status, $allowedStatuses, true)) {
            $filters['status'] = 'eq.' . $request->status;
        }

        // Validasi teks pencarian
        // Validasi dan bersihkan input pencarian
        $searchQuery = $request->input('q', '');
        if ($searchQuery && !preg_match('/^[\p{L}\p{N}\s\-_\.]{1,100}$/u', $searchQuery)) {
            $searchQuery = '';
        }

        // Ambil data pengajuan cuti dari Supabase
        $leaveRequests = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => $filters,
            'order' => 'created_at.desc',
        ]);

        // Lengkapi data cuti dengan informasi user dan tipe cuti
        $this->enrichLeaveRequests($leaveRequests);

        // Lakukan penyaringan data di memori jika ada query pencarian
        $searchQuery = $request->input('q', '');
        if ($searchQuery) {
            $q = strtolower($searchQuery);
            $leaveRequests = array_values(array_filter($leaveRequests, function ($leave) use ($q) {
                $userName = strtolower($leave['user']['full_name'] ?? '');
                $typeName = strtolower($leave['leave_type']['nama'] ?? '');
                $status = strtolower($leave['status'] ?? '');
                return str_contains($userName, $q) || str_contains($typeName, $q) || str_contains($status, $q);
            }));
        }

        // Ambil jenis-jenis cuti yang aktif
        $leaveTypes = $this->supabase->select('leave_types', 'id,nama', ['is_active' => 'true']);

        // Tampilkan halaman daftar cuti
        return view('leave.index', compact('leaveRequests', 'leaveTypes', 'searchQuery'));
    }

    public function create()
    {
        // Ambil ID user dari sesi
        $userId = Session::get('user_id');

        // Ambil pilihan tipe cuti yang aktif
        $leaveTypes = $this->supabase->select('leave_types', '*', [
            'is_active' => 'true',
        ]);

        // Cek sisa cuti user di tahun ini
        $balances = $this->supabase->select('leave_balances', '*', [
            'user_id' => $userId,
            'tahun' => date('Y'),
        ]);
        $leaveBalance = !empty($balances) ? (object) $balances[0] : (object) ['sisa' => 0];

        // Tampilkan halaman form pengajuan cuti
        return view('leave.create', compact('leaveTypes', 'leaveBalance'));
    }

    public function store(Request $request)
    {
        // Validasi data input dari form
        $request->validate([
            'leave_type_id' => 'required|string',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:20',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'agreement' => 'accepted',
        ]);

        // Ambil ID user yang login
        $userId = Session::get('user_id');

        // Menghitung total hari
        // Hitung total hari cuti yang diajukan
        $startDate = \Carbon\Carbon::parse($request->tanggal_mulai);
        $endDate = \Carbon\Carbon::parse($request->tanggal_selesai);
        $totalHari = $startDate->diffInDays($endDate) + 1;

        // Cek batas maksimal hari cuti
        // Pastikan tidak melebihi batas maksimal hari untuk tipe cuti yang dipilih
        $leaveType = $this->supabase->selectSingle('leave_types', 'id', $request->leave_type_id);
        if ($leaveType && $totalHari > $leaveType['max_hari_per_pengajuan']) {
            return back()->withErrors(['error' => "Total hari melebihi batas maksimal ({$leaveType['max_hari_per_pengajuan']} hari)."])->withInput();
        }

        // Overlap detection: check if user already has leave in this date range
        // Note: Database trigger also enforces this, but we check early for better UX
        // Cek jika pengajuan cuti bertabrakan dengan tanggal cuti yang sudah ada
        $existingLeaves = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => 'id,tanggal_mulai,tanggal_selesai,status',
            'filters' => [
                'user_id' => 'eq.' . $userId,
                'status' => 'in.(pending,disetujui)',
            ],
        ]);
        foreach ($existingLeaves as $existing) {
            if ($request->tanggal_mulai <= $existing['tanggal_selesai'] && $request->tanggal_selesai >= $existing['tanggal_mulai']) {
                return back()->withErrors(['error' => 'Tanggal cuti tumpang tindih dengan pengajuan yang sudah ada.'])->withInput();
            }
        }

        // Handle file upload
        // Proses upload file lampiran jika ada
        $lampiranUrl = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');

            // File validation (magic bytes)
            // Validasi jenis file (PDF atau gambar)
            $validMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $validMimes)) {
                return back()->withErrors(['lampiran' => 'Tipe file tidak valid.'])->withInput();
            }

            // Min size 10KB
            // Validasi agar ukuran file tidak terlalu kecil
            if ($file->getSize() < 10240) {
                return back()->withErrors(['lampiran' => 'Ukuran file minimal 10KB.'])->withInput();
            }

            // Strip EXIF data from images for privacy
            // Hapus metadata EXIF dari foto untuk menjaga privasi
            if (in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
                $this->stripExifData($file->getRealPath(), $file->getMimeType());
            }

            // Tentukan nama dan lokasi file di bucket
            $bucket = config('services.supabase.storage_bucket');
            $fileName = $userId . '/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Upload file ke Supabase storage
            $uploadResult = $this->supabase->uploadFile(
                $bucket,
                $fileName,
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            );

            if ($uploadResult['success']) {
                $lampiranUrl = $fileName;
            } else {
                return back()->withErrors(['lampiran' => 'Gagal upload file: ' . ($uploadResult['error'] ?? '')])->withInput();
            }
        }

        // Menyimpan pengajuan cuti
        // Susun data cuti untuk disimpan
        $data = [
            'user_id' => $userId,
            'leave_type_id' => $request->leave_type_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => strip_tags($request->alasan),
            'status' => 'pending',
        ];

        // Mengirim lampiran
        // schemas may not have lampiran_url yet, and sending null breaks inserts.
        // Simpan URL lampiran jika file berhasil diupload
        if ($lampiranUrl) {
            $data['lampiran_url'] = $lampiranUrl;
        }

        // Simpan pengajuan ke database
        $result = $this->supabase->insert('leave_requests', $data);

        if ($result['success']) {
            // Catat log aktivitas jika berhasil menyimpan pengajuan
            $requestId = $result['data'][0]['id'] ?? null;
            $this->activityLog->log('create', "Mengajukan cuti: {$leaveType['nama']} ({$totalHari} hari)", 'leave_request', $requestId);
            return redirect()->route('leave.index')->with('success', 'Pengajuan cuti berhasil dikirim.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal membuat pengajuan cuti.'])->withInput();
    }

    public function show(string $id)
    {
        // Dapatkan data user dari sesi saat ini
        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $departmentId = Session::get('user_department_id');

        // Validasi format ID
        // Validasi format ID pengajuan
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return redirect()->route('leave.index')->withErrors(['error' => 'Pengajuan tidak ditemukan.']);
        }

        // Mengambil data dan verifikasi akses
        // Cari pengajuan dan periksa hak aksesnya sesuai role user
        $leave = null;
        if ($role === 'karyawan') {
            // Karyawan hanya bisa melihat pengajuannya sendiri
            $leave = $this->supabase->selectSingle('leave_requests', 'id', $id);
            if (!$leave || $leave['user_id'] !== $userId) {
                $leave = null;
            }
        } elseif ($role === 'manager') {
            // Verifikasi manajer
            // Manager bisa melihat pengajuannya sendiri atau dari karyawan di departemennya
            $leave = $this->supabase->selectSingle('leave_requests', 'id', $id);
            if ($leave) {
                if ($leave['user_id'] !== $userId) {
                    $leaveOwner = $this->supabase->selectSingle('profiles', 'id', $leave['user_id'], 'department_id');
                    if (!$leaveOwner || ($leaveOwner['department_id'] ?? '') !== $departmentId) {
                        $leave = null;
                    }
                }
            }
        } elseif ($role === 'admin') {
            // Admin bisa melihat semua pengajuan cuti
            $leave = $this->supabase->selectSingle('leave_requests', 'id', $id);
        }

        if (!$leave) {
            return redirect()->route('leave.index')->withErrors(['error' => 'Pengajuan tidak ditemukan.']);
        }

        // Melengkapi data
        // Tambahkan informasi user dan tipe cuti ke dalam data pengajuan
        $leaves = [$leave];
        $this->enrichLeaveRequests($leaves);
        $leave = (object) $leaves[0];

        // Mengambil URL lampiran
        // Buat URL sementara untuk mengakses lampiran jika ada
        $signedUrl = null;
        if (!empty($leave->lampiran_url ?? null)) {
            $bucket = config('services.supabase.storage_bucket');
            $signedUrl = $this->supabase->getSignedUrl($bucket, $leave->lampiran_url);
        }

        // Mengambil nama penyetuju
        // Dapatkan nama penyetuju jika pengajuan sudah disetujui
        $approverName = null;
        if (!empty($leave->disetujui_oleh ?? null)) {
            $approver = $this->supabase->select('profiles', 'full_name', ['id' => $leave->disetujui_oleh]);
            $approverName = !empty($approver) ? $approver[0]['full_name'] : null;
        }

        // Tampilkan halaman detail pengajuan
        return view('leave.show', compact('leave', 'signedUrl', 'approverName'));
    }

    public function edit(string $id)
    {
        $userId = Session::get('user_id');
        
        // Ambil data pengajuan yang mau diedit
        $leave = $this->supabase->selectSingle('leave_requests', 'id', $id);

        // Hanya boleh mengedit jika pengajuan adalah milik sendiri dan statusnya masih pending
        if (!$leave || $leave['user_id'] !== $userId || $leave['status'] !== 'pending') {
            return redirect()->route('leave.index')->withErrors(['error' => 'Tidak bisa mengedit pengajuan ini.']);
        }

        // Ambil tipe cuti yang aktif dan informasi sisa saldo cuti
        $leaveTypes = $this->supabase->select('leave_types', '*', ['is_active' => 'true']);
        $balances = $this->supabase->select('leave_balances', '*', ['user_id' => $userId, 'tahun' => date('Y')]);
        $leaveBalance = !empty($balances) ? (object) $balances[0] : (object) ['sisa' => 0];

        // Tampilkan halaman edit
        return view('leave.edit', compact('leave', 'leaveTypes', 'leaveBalance'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Session::get('user_id');
        
        // Validasi input data
        $request->validate([
            'leave_type_id' => 'required|string',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:20',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // Hitung ulang total hari yang diajukan
        $totalHari = \Carbon\Carbon::parse($request->tanggal_mulai)->diffInDays(\Carbon\Carbon::parse($request->tanggal_selesai)) + 1;

        // Mengecek sisa cuti
        // Cek saldo cuti dengan mencocokkannya dengan tahun dari tanggal mulai cuti
        $leaveYear = date('Y', strtotime($request->tanggal_mulai));
        $leaveType = $this->supabase->selectSingle('leave_types', 'id', $request->leave_type_id);
        if ($leaveType && $leaveType['kode'] !== 'CTG') {
            $balances = $this->supabase->select('leave_balances', '*', [
                'user_id' => $userId,
                'tahun' => $leaveYear,
            ]);
            if (!empty($balances) && $balances[0]['sisa'] < $totalHari) {
                return back()->withErrors(['error' => 'Sisa cuti tidak mencukupi.'])->withInput();
            }
        }

        // Cek batas maksimal hari cuti
        // Cek apakah total hari melebihi batas aturan jenis cuti
        if ($leaveType && $totalHari > $leaveType['max_hari_per_pengajuan']) {
            return back()->withErrors(['error' => "Total hari melebihi batas maksimal ({$leaveType['max_hari_per_pengajuan']} hari)."])->withInput();
        }

        // Siapkan data pengajuan baru yang akan diupdate
        $data = [
            'leave_type_id' => $request->leave_type_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => strip_tags($request->alasan),
        ];

        // Mengambil data cuti lama
        // Dapatkan URL lampiran lama jika ada
        $oldLeave = $this->supabase->selectSingle('leave_requests', 'id', $id, 'lampiran_url');
        $oldLampiranUrl = $oldLeave['lampiran_url'] ?? null;

        // Handle file upload (same as store())
        // Proses upload file lampiran baru jika ada
        $lampiranUrl = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');

            // File validation (magic bytes)
            // Validasi jenis file
            $validMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $validMimes)) {
                return back()->withErrors(['lampiran' => 'Tipe file tidak valid.'])->withInput();
            }

            // Min size 10KB
            // Validasi ukuran minimal file
            if ($file->getSize() < 10240) {
                return back()->withErrors(['lampiran' => 'Ukuran file minimal 10KB.'])->withInput();
            }

            // Strip EXIF data from images for privacy
            // Buang data EXIF dari gambar
            if (in_array($file->getMimeType(), ['image/jpeg', 'image/png'])) {
                $this->stripExifData($file->getRealPath(), $file->getMimeType());
            }

            $bucket = config('services.supabase.storage_bucket');
            $fileName = $userId . '/' . Str::uuid() . '.' . $file->getClientOriginalExtension();

            $uploadResult = $this->supabase->uploadFile(
                $bucket,
                $fileName,
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            );

            if ($uploadResult['success']) {
                $lampiranUrl = $fileName;
                // Menghapus file lama
                // Jika file lama ada, hapus file tersebut dari storage
                if ($oldLampiranUrl) {
                    $this->supabase->deleteFile($bucket, [$oldLampiranUrl]);
                }
            } else {
                return back()->withErrors(['lampiran' => 'Gagal upload file: ' . ($uploadResult['error'] ?? '')])->withInput();
            }
        }

        // Include lampiran_url in data if a new file was uploaded
        // Perbarui data dengan URL lampiran baru jika ada file yang diunggah
        if ($lampiranUrl) {
            $data['lampiran_url'] = $lampiranUrl;
        }

        // Simpan pembaruan data ke database
        $result = $this->supabase->update('leave_requests', ['id' => $id, 'user_id' => $userId, 'status' => 'pending'], $data);

        if ($result['success']) {
            // Catat log jika berhasil diupdate
            $this->activityLog->log('update', 'Memperbarui pengajuan cuti', 'leave_request', $id);
            return redirect()->route('leave.index')->with('success', 'Pengajuan cuti berhasil diperbarui.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal memperbarui pengajuan.'])->withInput();
    }

    public function cancel(string $id)
    {
        $userId = Session::get('user_id');

        // Validasi format ID
        // Validasi keabsahan format ID
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json(['success' => false, 'message' => 'ID pengajuan tidak valid.'], 400);
        }

        // Ubah status pengajuan menjadi dibatalkan jika status awalnya masih pending
        $result = $this->supabase->update('leave_requests', [
            'id' => $id,
            'user_id' => $userId,
            'status' => 'pending',
        ], ['status' => 'dibatalkan'], true);

        if ($result['success']) {
            // Catat log pembatalan
            $this->activityLog->log('update', 'Membatalkan pengajuan cuti', 'leave_request', $id);
            return response()->json(['success' => true, 'message' => 'Pengajuan berhasil dibatalkan.']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal membatalkan pengajuan.'], 400);
    }

    public function pending()
    {
        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $departmentId = Session::get('user_department_id');

        // Hanya manajer dan admin
        // Pastikan halaman ini hanya bisa diakses admin dan manager
        if (!in_array($role, ['manager', 'admin'])) {
            return redirect()->route('leave.index');
        }

        // Mengambil data pengajuan
        // Ambil data pengajuan pending dengan proteksi sesuai role
        $pendingLeaves = [];
        if ($role === 'admin') {
            // Admin bisa melihat seluruh pengajuan yang pending
            $pendingLeaves = $this->supabase->selectAdvanced('leave_requests', [
                'columns' => '*',
                'filters' => ['status' => 'eq.pending'],
                'order' => 'created_at.asc',
            ], null, true);
        } elseif ($role === 'manager' && $departmentId) {
            // Mengambil ID tim
            // Manager harus mengambil ID anggota departemennya terlebih dahulu
            $teamMembers = $this->supabase->selectAdmin('profiles', 'id', [
                'department_id' => $departmentId,
                'is_active' => 'true',
            ]);
            $memberIds = array_column($teamMembers, 'id');

            // Ambil pengajuan pending dari anggota-anggota tersebut
            if (!empty($memberIds)) {
                $pendingLeaves = $this->supabase->selectAdvanced('leave_requests', [
                    'columns' => '*',
                    'filters' => [
                        'status' => 'eq.pending',
                        'user_id' => 'in.(' . implode(',', $memberIds) . ')',
                    ],
                    'order' => 'created_at.asc',
                ], null, true);
            }
        }

        // Cannot approve own request
        // Pastikan user tidak bisa menyetujui pengajuannya sendiri
        $pendingLeaves = array_values(array_filter($pendingLeaves, fn($l) => $l['user_id'] !== $userId));

        // Tambahkan informasi pendukung pada list pengajuan
        $this->enrichLeaveRequests($pendingLeaves);

        // Tampilkan halaman daftar tunggu persetujuan
        return view('leave.pending', compact('pendingLeaves'));
    }

    public function employeeRequests(Request $request)
    {
        $role = Session::get('user_role');
        
        // Halaman ini hanya boleh diakses oleh manager
        if ($role !== 'manager') {
            return redirect()->route('leave.index');
        }

        $departmentId = Session::get('user_department_id');
        
        // Ambil semua karyawan di bawah departemen manager ini
        $teamMembers = $departmentId
            ? $this->supabase->selectAdmin('profiles', 'id', [
                'department_id' => $departmentId,
                'is_active' => 'true',
            ])
            : [];
        $memberIds = array_column($teamMembers, 'id');

        $filters = ['user_id' => 'in.(' . implode(',', $memberIds) . ')'];
        
        // Whitelist valid status values to prevent PostgREST filter injection
        // Terapkan filter status jika diizinkan
        $allowedStatuses = ['pending', 'disetujui', 'ditolak', 'dibatalkan'];
        if ($request->has('status') && $request->status && in_array($request->status, $allowedStatuses, true)) {
            $filters['status'] = 'eq.' . $request->status;
        }

        // Validasi pencarian
        // Lakukan validasi terhadap query pencarian
        $searchQuery = $request->input('q', '');
        if ($searchQuery && !preg_match('/^[\p{L}\p{N}\s\-_\.]{1,100}$/u', $searchQuery)) {
            $searchQuery = '';
        }

        // Ambil daftar pengajuan karyawan di departemen terkait
        $leaveRequests = !empty($memberIds)
            ? $this->supabase->selectAdvanced('leave_requests', [
                'columns' => '*',
                'filters' => $filters,
                'order' => 'created_at.desc',
            ], null, true)
            : [];

        // Lengkapi data
        $this->enrichLeaveRequests($leaveRequests);

        // Tampilkan halaman riwayat pengajuan karyawan
        return view('leave.employee-requests', compact('leaveRequests'));
    }

    public function approve(Request $request, string $id)
    {
        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $departmentId = Session::get('user_department_id');

        // Pastikan yang mengakses ini minimal manager atau admin
        if (!in_array($role, ['manager', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        // Validasi format ID
        // Cek apakah format ID benar
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json(['success' => false, 'message' => 'ID pengajuan tidak valid.'], 400);
        }

        // Mengambil data cuti
        // Verifikasi akses manajer
        // Ambil data pengajuan dan pastikan yang bersangkutan berhak menyetujui
        $leave = null;
        if ($role === 'admin') {
            $leaves = $this->supabase->selectAdmin('leave_requests', '*', ['id' => $id]);
            $leave = !empty($leaves) ? $leaves[0] : null;
        } elseif ($role === 'manager' && $departmentId) {
            // Mengambil ID tim
            // Manager hanya dapat menyetujui timnya
            $teamMembers = $this->supabase->selectAdmin('profiles', 'id', [
                'department_id' => $departmentId,
                'is_active' => 'true',
            ]);
            $memberIds = array_column($teamMembers, 'id');

            if (empty($memberIds) || !in_array($userId, $memberIds)) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke pengajuan ini.'], 403);
            }

            // Mengambil data cuti
            $leaves = $this->supabase->selectAdmin('leave_requests', '*', [
                'id' => $id,
                'user_id' => 'in.(' . implode(',', $memberIds) . ')',
            ]);
            $leave = !empty($leaves) ? $leaves[0] : null;
        }

        // Pastikan datanya ada dan masih pending
        if (!$leave || $leave['status'] !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak valid.'], 400);
        }

        // Hindari menyetujui pengajuan milik sendiri
        if ($leave['user_id'] === $userId) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa menyetujui pengajuan sendiri.'], 403);
        }

        // Mengecek sisa cuti
        // Ambil informasi jenis cuti untuk keperluan pengecekan
        $leaveType = $this->supabase->selectAdmin('leave_types', 'kode', ['id' => $leave['leave_type_id']]);
        $leaveTypeCode = $leaveType[0]['kode'] ?? null;

        // Ubah status ke disetujui di database
        $result = $this->supabase->update('leave_requests', ['id' => $id], [
            'status' => 'disetujui',
            'disetujui_oleh' => $userId,
        ], true);

        if ($result['success']) {
            // Log persetujuan
            $this->activityLog->log('approve', 'Menyetujui pengajuan cuti', 'leave_request', $id);
            return response()->json(['success' => true, 'message' => 'Pengajuan berhasil disetujui.']);
        }

        // Cek sisa cuti
        // Jika saldo habis, tampilkan error yang relevan dari trigger database
        $errorMsg = $result['error'] ?? '';
        if (str_contains($errorMsg, 'Saldo cuti tidak mencukupi') || str_contains($errorMsg, 'Saldo cuti tidak ditemukan')) {
            return response()->json(['success' => false, 'message' => $errorMsg], 400);
        }

        return response()->json(['success' => false, 'message' => 'Gagal menyetujui pengajuan.'], 500);
    }

    public function reject(Request $request, string $id)
    {
        // Validasi isian alasan penolakan
        $request->validate([
            'alasan' => 'required|string|min:10',
        ]);

        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $departmentId = Session::get('user_department_id');

        // Pastikan hak akses memadai
        if (!in_array($role, ['manager', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        // Validasi format ID
        // Pastikan format ID pengajuan benar
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $id)) {
            return response()->json(['success' => false, 'message' => 'ID pengajuan tidak valid.'], 400);
        }

        // Mengambil data cuti
        // Verifikasi akses manajer
        // Ambil data pengajuan dengan mempertimbangkan wilayah wewenang
        $leave = null;
        if ($role === 'admin') {
            $leaves = $this->supabase->selectAdmin('leave_requests', '*', ['id' => $id]);
            $leave = !empty($leaves) ? $leaves[0] : null;
        } elseif ($role === 'manager' && $departmentId) {
            // Mengambil ID tim
            $teamMembers = $this->supabase->selectAdmin('profiles', 'id', [
                'department_id' => $departmentId,
                'is_active' => 'true',
            ]);
            $memberIds = array_column($teamMembers, 'id');

            if (empty($memberIds) || !in_array($userId, $memberIds)) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke pengajuan ini.'], 403);
            }

            // Mengambil data cuti
            $leaves = $this->supabase->selectAdmin('leave_requests', '*', [
                'id' => $id,
                'user_id' => 'in.(' . implode(',', $memberIds) . ')',
            ]);
            $leave = !empty($leaves) ? $leaves[0] : null;
        }

        // Pastikan pengajuan valid
        if (!$leave || $leave['status'] !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak valid.'], 400);
        }

        // Mencegah user menolak pengajuannya sendiri
        if ($leave['user_id'] === $userId) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa menolak pengajuan sendiri.'], 403);
        }

        // Simpan status ditolak
        $result = $this->supabase->update('leave_requests', ['id' => $id], [
            'status' => 'ditolak',
            'disetujui_oleh' => $userId,
        ], true);

        if ($result['success']) {
            // Tulis log mengenai penolakan tersebut
            $this->activityLog->log('reject', 'Menolak pengajuan cuti: ' . strip_tags($request->alasan), 'leave_request', $id);
            return response()->json(['success' => true, 'message' => 'Pengajuan berhasil ditolak.']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal menolak pengajuan.'], 500);
    }

    public function history()
    {
        $userId = Session::get('user_id');

        // Ambil riwayat pengajuan cuti user yang sudah tidak berstatus pending
        $leaves = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => [
                'user_id' => 'eq.' . $userId,
                'status' => 'neq.pending',
            ],
            'order' => 'created_at.desc',
        ]);

        // Lengkapi data pengajuan dengan user dan tipe cuti
        $this->enrichLeaveRequests($leaves);

        // Tampilkan halaman riwayat
        return view('leave.history', compact('leaves'));
    }

    protected function enrichLeaveRequests(array &$leaves): void
    {
        $profileCache = [];
        $typeCache = [];
        $bucket = config('services.supabase.storage_bucket');

        // Loop untuk menambah rincian informasi pada masing-masing pengajuan cuti
        foreach ($leaves as &$leave) {
            $uid = $leave['user_id'] ?? '';
            // Gunakan cache profil agar tidak berulang kali menembak database
            if (!isset($profileCache[$uid])) {
                $profiles = $this->supabase->select('profiles', 'full_name,profile_photo_url', ['id' => $uid]);
                $profileCache[$uid] = !empty($profiles) ? $profiles[0] : ['full_name' => 'Unknown', 'profile_photo_url' => null];

                // Jika user memiliki foto profil, buat link aman (signed url)
                if (!empty($profileCache[$uid]['profile_photo_url'])) {
                    $signed = $this->supabase->getSignedUrl($bucket, $profileCache[$uid]['profile_photo_url'], 604800);
                    if ($signed) {
                        $profileCache[$uid]['profile_photo_url'] = $signed;
                    }
                }
            }
            $leave['user'] = $profileCache[$uid];

            // Gunakan cache untuk informasi tipe cuti
            $tid = $leave['leave_type_id'] ?? '';
            if (!isset($typeCache[$tid])) {
                $types = $this->supabase->select('leave_types', 'nama,kode', ['id' => $tid]);
                $typeCache[$tid] = !empty($types) ? $types[0] : ['nama' => '-', 'kode' => '-'];
            }
            $leave['leave_type'] = $typeCache[$tid];
        }
    }

    /**
     * Strip EXIF metadata from uploaded images for privacy.
     * Includes dimension validation to prevent image bomb / memory exhaustion attacks.
     */
    protected function stripExifData(string $filePath, string $mimeType): void
    {
        try {
            // Validasi dimensi gambar
            // to prevent image bomb attacks (small file, massive pixel count)
            // Validasi dulu resolusi gambarnya agar tidak menguras memori server
            $imageInfo = @getimagesize($filePath);
            if ($imageInfo) {
                $pixelCount = $imageInfo[0] * $imageInfo[1];
                if ($pixelCount > self::MAX_IMAGE_PIXELS) {
                    \Illuminate\Support\Facades\Log::warning('Image bomb rejected', [
                        'dimensions' => $imageInfo[0] . 'x' . $imageInfo[1],
                        'pixels' => $pixelCount,
                    ]);
                    return; // Skip EXIF stripping — image is too large to safely process
                }
            }

            // Muat ulang gambar dan simpan balik untuk menghilangkan metadata EXIF
            if ($mimeType === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
                $image = imagecreatefromjpeg($filePath);
                if ($image) {
                    imagejpeg($image, $filePath, 90);
                    imagedestroy($image);
                }
            } elseif ($mimeType === 'image/png' && function_exists('imagecreatefrompng')) {
                $image = imagecreatefrompng($filePath);
                if ($image) {
                    imagepng($image, $filePath, 9);
                    imagedestroy($image);
                }
            }
        } catch (\Exception $e) {
            // Non-critical: log but don't fail the upload
            // Catat log jika proses menghapus EXIF gagal, tapi jangan batalkan upload
            \Illuminate\Support\Facades\Log::warning('EXIF strip failed: ' . $e->getMessage());
        }
    }
}
