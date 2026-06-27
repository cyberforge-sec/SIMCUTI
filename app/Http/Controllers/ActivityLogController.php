<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLog;
    protected SupabaseService $supabase;

    // Menginisialisasi class dan dependensi
    public function __construct(ActivityLogService $activityLog, SupabaseService $supabase)
    {
        $this->activityLog = $activityLog;
        $this->supabase = $supabase;
    }

    // Menampilkan halaman utama atau daftar data
    public function index()
    {
        return view('activity-logs.index');
    }

    // Fungsi untuk menangani proses data
    public function data(Request $request)
    {
        $options = [
            'limit' => $request->limit ?? 50,
            'offset' => $request->offset ?? 0,
        ];

        if ($request->filled('user_id') && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $request->user_id)) {
            $options['user_id'] = $request->user_id;
        }
        if ($request->filled('aksi')) {
            $allowedAksi = ['login', 'logout', 'create', 'update', 'delete', 'approve', 'reject', '2fa_verify'];
            if (in_array($request->aksi, $allowedAksi, true)) {
                $options['aksi'] = $request->aksi;
            }
        }
        if ($request->filled('model_type')) {
            $allowedModels = ['profile', 'leave_request', 'department', 'leave_type'];
            if (in_array($request->model_type, $allowedModels, true)) {
                $options['model_type'] = $request->model_type;
            }
        }
        if ($request->filled('date_from') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->date_from)) {
            $options['date_from'] = $request->date_from;
        }
        if ($request->filled('date_to') && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->date_to)) {
            $options['date_to'] = $request->date_to;
        }

        $logs = $this->activityLog->getLogs($options);
        $total = $this->activityLog->countLogs();

        // Enrich with user names
        $profileCache = [];
        foreach ($logs as &$log) {
            $uid = $log['user_id'] ?? '';
            if (!isset($profileCache[$uid])) {
                $profiles = $this->supabase->select('profiles', 'full_name', ['id' => $uid]);
                $profileCache[$uid] = !empty($profiles) ? $profiles[0]['full_name'] : 'Unknown';
            }
            $log['user_name'] = $profileCache[$uid];
        }

        return response()->json([
            'data' => $logs,
            'total' => $total,
        ]);
    }
}
