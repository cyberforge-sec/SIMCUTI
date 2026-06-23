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
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    public function index(Request $request)
    {
        $userId = Session::get('user_id');

        $filters = ['user_id' => 'eq.' . $userId];

        // Whitelist valid status values to prevent PostgREST filter injection
        $allowedStatuses = ['pending', 'disetujui', 'ditolak', 'dibatalkan'];
        if ($request->has('status') && $request->status && in_array($request->status, $allowedStatuses, true)) {
            $filters['status'] = 'eq.' . $request->status;
        }

        $leaveRequests = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => $filters,
            'order' => 'created_at.desc',
        ]);

        $this->enrichLeaveRequests($leaveRequests);

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

        $leaveTypes = $this->supabase->select('leave_types', 'id,nama', ['is_active' => 'true']);

        return view('leave.index', compact('leaveRequests', 'leaveTypes', 'searchQuery'));
    }

    public function create()
    {
        $userId = Session::get('user_id');

        $leaveTypes = $this->supabase->select('leave_types', '*', [
            'is_active' => 'true',
        ]);

        $balances = $this->supabase->select('leave_balances', '*', [
            'user_id' => $userId,
            'tahun' => date('Y'),
        ]);
        $leaveBalance = !empty($balances) ? (object) $balances[0] : (object) ['sisa' => 0];

        return view('leave.create', compact('leaveTypes', 'leaveBalance'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|string',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:20',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'agreement' => 'accepted',
        ]);

        $userId = Session::get('user_id');

        // Calculate total days
        $startDate = \Carbon\Carbon::parse($request->tanggal_mulai);
        $endDate = \Carbon\Carbon::parse($request->tanggal_selesai);
        $totalHari = $startDate->diffInDays($endDate) + 1;

        // Check leave balance using the year of the leave request, not current year
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

        // Check max days per leave type
        if ($leaveType && $totalHari > $leaveType['max_hari_per_pengajuan']) {
            return back()->withErrors(['error' => "Total hari melebihi batas maksimal ({$leaveType['max_hari_per_pengajuan']} hari)."])->withInput();
        }

        // Overlap detection: check if user already has leave in this date range
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
        $lampiranUrl = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');

            // File validation (magic bytes)
            $validMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $validMimes)) {
                return back()->withErrors(['lampiran' => 'Tipe file tidak valid.'])->withInput();
            }

            // Min size 10KB
            if ($file->getSize() < 10240) {
                return back()->withErrors(['lampiran' => 'Ukuran file minimal 10KB.'])->withInput();
            }

            // Strip EXIF data from images for privacy
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
            } else {
                return back()->withErrors(['lampiran' => 'Gagal upload file: ' . ($uploadResult['error'] ?? '')])->withInput();
            }
        }

        // Create leave request
        $data = [
            'user_id' => $userId,
            'leave_type_id' => $request->leave_type_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => strip_tags($request->alasan),
            'status' => 'pending',
            'lampiran_url' => $lampiranUrl,
        ];

        $result = $this->supabase->insert('leave_requests', $data);

        if ($result['success']) {
            $requestId = $result['data'][0]['id'] ?? null;
            $this->activityLog->log('create', "Mengajukan cuti: {$leaveType['nama']} ({$totalHari} hari)", 'leave_request', $requestId);
            return redirect()->route('leave.index')->with('success', 'Pengajuan cuti berhasil dikirim.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal membuat pengajuan cuti.'])->withInput();
    }

    public function show(string $id)
    {
        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $departmentId = Session::get('user_department_id');

        $leave = $this->supabase->selectSingle('leave_requests', 'id', $id);
        if (!$leave) {
            return redirect()->route('leave.index')->withErrors(['error' => 'Pengajuan tidak ditemukan.']);
        }

        // Authorization check: user can only view own requests, manager can view team, admin can view all
        if ($role === 'karyawan') {
            if ($leave['user_id'] !== $userId) {
                abort(403, 'Anda tidak memiliki akses ke pengajuan ini.');
            }
        } elseif ($role === 'manager') {
            if ($leave['user_id'] !== $userId) {
                $leaveOwner = $this->supabase->selectSingle('profiles', 'id', $leave['user_id'], 'department_id');
                if (!$leaveOwner || ($leaveOwner['department_id'] ?? '') !== $departmentId) {
                    abort(403, 'Anda tidak memiliki akses ke pengajuan ini.');
                }
            }
        }

        // Fix: enrich via reference properly
        $leaves = [$leave];
        $this->enrichLeaveRequests($leaves);
        $leave = (object) $leaves[0];

        // Get signed URL for attachment if exists
        $signedUrl = null;
        if (!empty($leave->lampiran_url ?? null)) {
            $bucket = config('services.supabase.storage_bucket');
            $signedUrl = $this->supabase->getSignedUrl($bucket, $leave->lampiran_url);
        }

        // Get approver name
        $approverName = null;
        if (!empty($leave->disetujui_oleh ?? null)) {
            $approver = $this->supabase->select('profiles', 'full_name', ['id' => $leave->disetujui_oleh]);
            $approverName = !empty($approver) ? $approver[0]['full_name'] : null;
        }

        return view('leave.show', compact('leave', 'signedUrl', 'approverName'));
    }

    public function edit(string $id)
    {
        $userId = Session::get('user_id');
        $leave = $this->supabase->selectSingle('leave_requests', 'id', $id);

        if (!$leave || $leave['user_id'] !== $userId || $leave['status'] !== 'pending') {
            return redirect()->route('leave.index')->withErrors(['error' => 'Tidak bisa mengedit pengajuan ini.']);
        }

        $leaveTypes = $this->supabase->select('leave_types', '*', ['is_active' => 'true']);
        $balances = $this->supabase->select('leave_balances', '*', ['user_id' => $userId, 'tahun' => date('Y')]);
        $leaveBalance = !empty($balances) ? (object) $balances[0] : (object) ['sisa' => 0];

        return view('leave.edit', compact('leave', 'leaveTypes', 'leaveBalance'));
    }

    public function update(Request $request, string $id)
    {
        $userId = Session::get('user_id');
        $request->validate([
            'leave_type_id' => 'required|string',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'alasan' => 'required|string|min:20',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $totalHari = \Carbon\Carbon::parse($request->tanggal_mulai)->diffInDays(\Carbon\Carbon::parse($request->tanggal_selesai)) + 1;

        // Check leave balance using the year of the leave request, not current year
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

        // Check max days per leave type
        if ($leaveType && $totalHari > $leaveType['max_hari_per_pengajuan']) {
            return back()->withErrors(['error' => "Total hari melebihi batas maksimal ({$leaveType['max_hari_per_pengajuan']} hari)."])->withInput();
        }

        $data = [
            'leave_type_id' => $request->leave_type_id,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'total_hari' => $totalHari,
            'alasan' => strip_tags($request->alasan),
        ];

        // Fetch old leave request to get existing lampiran_url
        $oldLeave = $this->supabase->selectSingle('leave_requests', 'id', $id, 'lampiran_url');
        $oldLampiranUrl = $oldLeave['lampiran_url'] ?? null;

        // Handle file upload (same as store())
        $lampiranUrl = null;
        if ($request->hasFile('lampiran')) {
            $file = $request->file('lampiran');

            // File validation (magic bytes)
            $validMimes = ['application/pdf', 'image/jpeg', 'image/png'];
            if (!in_array($file->getMimeType(), $validMimes)) {
                return back()->withErrors(['lampiran' => 'Tipe file tidak valid.'])->withInput();
            }

            // Min size 10KB
            if ($file->getSize() < 10240) {
                return back()->withErrors(['lampiran' => 'Ukuran file minimal 10KB.'])->withInput();
            }

            // Strip EXIF data from images for privacy
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
                // Delete old file from storage if exists
                if ($oldLampiranUrl) {
                    $this->supabase->deleteFile($bucket, [$oldLampiranUrl]);
                }
            } else {
                return back()->withErrors(['lampiran' => 'Gagal upload file: ' . ($uploadResult['error'] ?? '')])->withInput();
            }
        }

        // Include lampiran_url in data if a new file was uploaded
        if ($lampiranUrl) {
            $data['lampiran_url'] = $lampiranUrl;
        }

        $result = $this->supabase->update('leave_requests', ['id' => $id, 'user_id' => $userId, 'status' => 'pending'], $data);

        if ($result['success']) {
            $this->activityLog->log('update', 'Memperbarui pengajuan cuti', 'leave_request', $id);
            return redirect()->route('leave.index')->with('success', 'Pengajuan cuti berhasil diperbarui.');
        }

        return back()->withErrors(['error' => $result['error'] ?? 'Gagal memperbarui pengajuan.'])->withInput();
    }

    public function cancel(string $id)
    {
        $userId = Session::get('user_id');

        $result = $this->supabase->update('leave_requests', [
            'id' => $id,
            'user_id' => $userId,
            'status' => 'pending',
        ], ['status' => 'dibatalkan'], true);

        if ($result['success']) {
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

        // Get pending requests from same department
        $pendingLeaves = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => ['status' => 'eq.pending'],
            'order' => 'created_at.asc',
        ], null, true);

        // Filter by department for manager
        if ($role === 'manager' && $departmentId) {
            $teamMembers = $this->supabase->select('profiles', 'id', ['department_id' => $departmentId]);
            $memberIds = array_column($teamMembers, 'id');
            $pendingLeaves = array_filter($pendingLeaves, fn($l) => in_array($l['user_id'], $memberIds));
        }

        // Cannot approve own request
        $pendingLeaves = array_values(array_filter($pendingLeaves, fn($l) => $l['user_id'] !== $userId));

        $this->enrichLeaveRequests($pendingLeaves);

        return view('leave.pending', compact('pendingLeaves'));
    }

    public function employeeRequests(Request $request)
    {
        $role = Session::get('user_role');
        if ($role !== 'manager') {
            return redirect()->route('leave.index');
        }

        $departmentId = Session::get('user_department_id');
        $teamMembers = $departmentId
            ? $this->supabase->select('profiles', 'id', ['department_id' => $departmentId])
            : [];
        $memberIds = array_column($teamMembers, 'id');

        $filters = ['user_id' => 'in.(' . implode(',', $memberIds) . ')'];
        // Whitelist valid status values to prevent PostgREST filter injection
        $allowedStatuses = ['pending', 'disetujui', 'ditolak', 'dibatalkan'];
        if ($request->has('status') && $request->status && in_array($request->status, $allowedStatuses, true)) {
            $filters['status'] = 'eq.' . $request->status;
        }

        $leaveRequests = !empty($memberIds)
            ? $this->supabase->selectAdvanced('leave_requests', [
                'columns' => '*',
                'filters' => $filters,
                'order' => 'created_at.desc',
            ], null, true)
            : [];

        $this->enrichLeaveRequests($leaveRequests);

        return view('leave.employee-requests', compact('leaveRequests'));
    }

    public function approve(Request $request, string $id)
    {
        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $departmentId = Session::get('user_department_id');

        if (!in_array($role, ['manager', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $leaves = $this->supabase->selectAdmin('leave_requests', '*', ['id' => $id]);
        $leave = !empty($leaves) ? $leaves[0] : null;
        if (!$leave || $leave['status'] !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak valid.'], 400);
        }

        if ($leave['user_id'] === $userId) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa menyetujui pengajuan sendiri.'], 403);
        }

        // Department scoping: managers can only approve requests from their own department
        if ($role === 'manager') {
            $leaveOwnerProfiles = $this->supabase->selectAdmin('profiles', 'department_id', ['id' => $leave['user_id']]);
            $leaveOwnerDeptId = $leaveOwnerProfiles[0]['department_id'] ?? null;
            if ($leaveOwnerDeptId !== $departmentId) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke pengajuan ini.'], 403);
            }
        }

        // Check leave balance (skip for unpaid leave / CTG)
        $leaveType = $this->supabase->selectAdmin('leave_types', 'kode', ['id' => $leave['leave_type_id']]);
        $leaveTypeCode = $leaveType[0]['kode'] ?? null;

        if ($leaveTypeCode !== 'CTG') {
            $balances = $this->supabase->selectAdmin('leave_balances', '*', [
                'user_id' => $leave['user_id'],
                'tahun' => date('Y', strtotime($leave['tanggal_mulai'])),
            ]);
            if (!empty($balances) && $balances[0]['sisa'] < $leave['total_hari']) {
                return response()->json(['success' => false, 'message' => 'Saldo cuti tidak mencukupi.'], 400);
            }
        }

        $result = $this->supabase->update('leave_requests', ['id' => $id], [
            'status' => 'disetujui',
            'disetujui_oleh' => $userId,
        ], true);

        if ($result['success']) {
            $this->activityLog->log('approve', 'Menyetujui pengajuan cuti', 'leave_request', $id);
            return response()->json(['success' => true, 'message' => 'Pengajuan berhasil disetujui.']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal menyetujui pengajuan.'], 500);
    }

    public function reject(Request $request, string $id)
    {
        $request->validate([
            'alasan' => 'required|string|min:10',
        ]);

        $userId = Session::get('user_id');
        $role = Session::get('user_role');
        $departmentId = Session::get('user_department_id');

        if (!in_array($role, ['manager', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses.'], 403);
        }

        $leaves = $this->supabase->selectAdmin('leave_requests', '*', ['id' => $id]);
        $leave = !empty($leaves) ? $leaves[0] : null;
        if (!$leave || $leave['status'] !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Pengajuan tidak valid.'], 400);
        }

        if ($leave['user_id'] === $userId) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa menolak pengajuan sendiri.'], 403);
        }

        // Department scoping: managers can only reject requests from their own department
        if ($role === 'manager') {
            $leaveOwnerProfiles = $this->supabase->selectAdmin('profiles', 'department_id', ['id' => $leave['user_id']]);
            $leaveOwnerDeptId = $leaveOwnerProfiles[0]['department_id'] ?? null;
            if ($leaveOwnerDeptId !== $departmentId) {
                return response()->json(['success' => false, 'message' => 'Anda tidak memiliki akses ke pengajuan ini.'], 403);
            }
        }

        $result = $this->supabase->update('leave_requests', ['id' => $id], [
            'status' => 'ditolak',
            'disetujui_oleh' => $userId,
        ], true);

        if ($result['success']) {
            $this->activityLog->log('reject', 'Menolak pengajuan cuti: ' . $request->alasan, 'leave_request', $id);
            return response()->json(['success' => true, 'message' => 'Pengajuan berhasil ditolak.']);
        }

        return response()->json(['success' => false, 'message' => 'Gagal menolak pengajuan.'], 500);
    }

    public function history()
    {
        $userId = Session::get('user_id');

        $leaves = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => [
                'user_id' => 'eq.' . $userId,
                'status' => 'neq.pending',
            ],
            'order' => 'created_at.desc',
        ]);

        $this->enrichLeaveRequests($leaves);

        return view('leave.history', compact('leaves'));
    }

    protected function enrichLeaveRequests(array &$leaves): void
    {
        $profileCache = [];
        $typeCache = [];
        $bucket = config('services.supabase.storage_bucket');

        foreach ($leaves as &$leave) {
            $uid = $leave['user_id'] ?? '';
            if (!isset($profileCache[$uid])) {
                $profiles = $this->supabase->select('profiles', 'full_name,profile_photo_url', ['id' => $uid]);
                $profileCache[$uid] = !empty($profiles) ? $profiles[0] : ['full_name' => 'Unknown', 'profile_photo_url' => null];

                if (!empty($profileCache[$uid]['profile_photo_url'])) {
                    $signed = $this->supabase->getSignedUrl($bucket, $profileCache[$uid]['profile_photo_url'], 604800);
                    if ($signed) {
                        $profileCache[$uid]['profile_photo_url'] = $signed;
                    }
                }
            }
            $leave['user'] = $profileCache[$uid];

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
            // Validate image dimensions BEFORE loading into memory
            // to prevent image bomb attacks (small file, massive pixel count)
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
            \Illuminate\Support\Facades\Log::warning('EXIF strip failed: ' . $e->getMessage());
        }
    }
}
