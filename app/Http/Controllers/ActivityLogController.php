<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLog;
    protected SupabaseService $supabase;

    public function __construct(ActivityLogService $activityLog, SupabaseService $supabase)
    {
        $this->activityLog = $activityLog;
        $this->supabase = $supabase;
    }

    public function index()
    {
        return view('activity-logs.index');
    }

    public function data(Request $request)
    {
        $options = [
            'limit' => $request->limit ?? 50,
            'offset' => $request->offset ?? 0,
        ];

        if ($request->filled('user_id')) {
            $options['user_id'] = $request->user_id;
        }
        if ($request->filled('aksi')) {
            $options['aksi'] = $request->aksi;
        }
        if ($request->filled('model_type')) {
            $options['model_type'] = $request->model_type;
        }
        if ($request->filled('date_from')) {
            $options['date_from'] = $request->date_from;
        }
        if ($request->filled('date_to')) {
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
