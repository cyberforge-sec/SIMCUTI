<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Support\Facades\Session;

class DashboardController extends Controller
{
    protected SupabaseService $supabase;
    protected ActivityLogService $activityLog;

    // Menginisialisasi class dan dependensi
    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    // Menentukan tampilan dashboard sesuai dengan role pengguna yang login
    public function index()
    {
        $userId = Session::get('user_id');
        $role = Session::get('user_role');

        return match ($role) {
            'admin' => $this->adminDashboard($userId),
            'manager' => $this->managerDashboard($userId),
            default => $this->karyawanDashboard($userId),
        };
    }

    // Menyiapkan data untuk dashboard karyawan (sisa cuti, riwayat, dll)
    protected function karyawanDashboard(string $userId)
    {
        // Ambil sisa cuti pengguna untuk tahun ini
        $balances = $this->supabase->select('leave_balances', '*', [
            'user_id' => $userId,
            'tahun' => date('Y'),
        ]);
        $leaveBalance = !empty($balances) ? (object) $balances[0] : (object) ['total_jatah' => 12, 'terpakai' => 0, 'sisa' => 12];

        $myLeaves = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => ['user_id' => 'eq.' . $userId],
            'order' => 'created_at.desc',
        ]);

        // Hitung total pengajuan cuti berdasarkan masing-masing status
        $approvedCount = 0;
        $pendingCount = 0;
        $rejectedCount = 0;
        $statusChart = ['pending' => 0, 'disetujui' => 0, 'ditolak' => 0, 'dibatalkan' => 0];

        foreach ($myLeaves as $leave) {
            $status = $leave['status'];
            if (isset($statusChart[$status])) {
                $statusChart[$status]++;
            }
            if ($status === 'disetujui') $approvedCount++;
            if ($status === 'pending') $pendingCount++;
            if ($status === 'ditolak') $rejectedCount++;
        }

        $recentLeaves = array_slice($myLeaves, 0, 5);

        $typeCache = [];
        foreach ($recentLeaves as &$leave) {
            $tid = $leave['leave_type_id'] ?? '';
            if (!isset($typeCache[$tid])) {
                $types = $this->supabase->select('leave_types', 'nama,kode', ['id' => $tid]);
                $typeCache[$tid] = !empty($types) ? $types[0] : ['nama' => '-', 'kode' => '-'];
            }
            $leave['leave_type'] = $typeCache[$tid];
        }

        return view('dashboard.index', compact(
            'leaveBalance', 'approvedCount', 'pendingCount', 'rejectedCount', 'statusChart', 'recentLeaves'
        ));
    }

    // Menyiapkan data dashboard untuk manager (berisi ringkasan cuti timnya)
    protected function managerDashboard(string $userId)
    {
        // Cari tahu manager ini berada di departemen mana
        $profiles = $this->supabase->select('profiles', '*', ['id' => $userId]);
        $profile = !empty($profiles) ? $profiles[0] : null;
        $departmentId = $profile['department_id'] ?? null;

        $teamMembers = [];
        $teamSize = 0;
        if ($departmentId) {
            $teamMembers = $this->supabase->select('profiles', '*', [
                'department_id' => $departmentId,
                'is_active' => 'true',
            ]);
            $teamSize = count($teamMembers);
        }

        $memberIds = array_column($teamMembers, 'id');
        $memberIdSet = array_flip($memberIds);

        $pendingCount = 0;
        $pendingApprovals = [];
        $totalRequests = 0;
        $approvedThisMonth = 0;
        $statusChart = ['pending' => 0, 'disetujui' => 0, 'ditolak' => 0, 'dibatalkan' => 0];
        $onLeaveUserIds = [];

        if (!empty($memberIds)) {
            $teamLeaves = $this->supabase->selectAdvanced('leave_requests', [
                'columns' => '*',
                'order' => 'created_at.desc',
            ], null, true);

            $today = date('Y-m-d');
            $currentMonth = date('m');

            foreach ($teamLeaves as $leave) {
                if (!isset($memberIdSet[$leave['user_id']])) {
                    continue;
                }

                $totalRequests++;
                $status = $leave['status'];
                if (isset($statusChart[$status])) $statusChart[$status]++;

                if ($status === 'pending') {
                    $pendingCount++;
                    if (count($pendingApprovals) < 5) {
                        $pendingApprovals[] = $leave;
                    }
                }

                if ($status === 'disetujui') {
                    if ($leave['tanggal_mulai'] <= $today && $leave['tanggal_selesai'] >= $today) {
                        $onLeaveUserIds[$leave['user_id']] = true;
                    }
                    if (date('m', strtotime($leave['updated_at'] ?? '')) == $currentMonth) {
                        $approvedThisMonth++;
                    }
                }
            }
        }

        $onLeaveToday = count($onLeaveUserIds);
        $monthlyTrend = $this->getMonthlyTrend();

        $totalDecided = $statusChart['disetujui'] + $statusChart['ditolak'];
        $approvalRate = $totalDecided > 0 ? round(($statusChart['disetujui'] / $totalDecided) * 100) : 0;

        $teamMemberMap = [];
        foreach ($teamMembers as $member) {
            $teamMemberMap[$member['id']] = $member;
        }

        $typeCache = [];
        foreach ($pendingApprovals as &$approval) {
            $approval['user'] = $teamMemberMap[$approval['user_id']]
                ?? ['full_name' => 'Unknown', 'email' => '', 'profile_photo_url' => null];

            $tid = $approval['leave_type_id'] ?? '';
            if (!isset($typeCache[$tid])) {
                $types = $this->supabase->select('leave_types', 'nama', ['id' => $tid]);
                $typeCache[$tid] = !empty($types) ? $types[0] : ['nama' => '-'];
            }
            $approval['leave_type'] = $typeCache[$tid];
        }

        $bucket = config('services.supabase.storage_bucket');
        foreach ($teamMembers as &$member) {
            $member['is_on_leave'] = isset($onLeaveUserIds[$member['id']]);
            if (!isset($member['email'])) $member['email'] = '';
            if (!empty($member['profile_photo_url'])) {
                $signed = $this->supabase->getSignedUrl($bucket, $member['profile_photo_url'], 604800);
                if ($signed) $member['profile_photo_url'] = $signed;
            }
        }

        return view('dashboard.index', compact(
            'teamSize', 'pendingCount', 'onLeaveToday', 'approvedThisMonth',
            'pendingApprovals', 'statusChart', 'monthlyTrend', 'teamMembers',
            'approvalRate', 'totalRequests'
        ));
    }

    // Menyiapkan data dashboard untuk admin (melihat keseluruhan data aplikasi)
    protected function adminDashboard(string $userId)
    {
        // Ambil semua data pengguna yang masih aktif
        $allProfiles = $this->supabase->select('profiles', '*', ['is_active' => 'true']);
        $totalUsers = count($allProfiles);

        $departments = $this->supabase->select('departments', '*', ['is_active' => 'true']);
        $totalDepartments = count($departments);

        $allLeaves = $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'order' => 'created_at.desc',
        ], null, true);

        $profileMap = [];
        foreach ($allProfiles as $p) {
            $profileMap[$p['id']] = $p;
        }

        $deptMap = [];
        foreach ($departments as $d) {
            $deptMap[$d['id']] = $d;
        }

        $pendingCount = 0;
        $approvedCount = 0;
        $rejectedCount = 0;
        $statusChart = ['pending' => 0, 'disetujui' => 0, 'ditolak' => 0, 'dibatalkan' => 0];
        $deptLeaveCount = [];
        $userLeaveCounts = [];
        $currentMonth = date('m');

        foreach ($allLeaves as $leave) {
            $status = $leave['status'];
            if (isset($statusChart[$status])) $statusChart[$status]++;
            if ($status === 'pending') $pendingCount++;
            if ($status === 'disetujui') $approvedCount++;
            if ($status === 'ditolak' && date('m', strtotime($leave['updated_at'] ?? '')) == $currentMonth) {
                $rejectedCount++;
            }

            $leaveUser = $profileMap[$leave['user_id']] ?? null;
            if ($leaveUser) {
                $did = $leaveUser['department_id'] ?? '';
                if ($did) {
                    $deptLeaveCount[$did] = ($deptLeaveCount[$did] ?? 0) + 1;
                }
            }

            $uid = $leave['user_id'];
            $userLeaveCounts[$uid] = ($userLeaveCounts[$uid] ?? 0) + 1;
        }

        $monthlyTrend = $this->getMonthlyTrend();

        $departmentChart = ['labels' => [], 'data' => []];
        foreach ($departments as $dept) {
            $departmentChart['labels'][] = $dept['nama'];
            $departmentChart['data'][] = $deptLeaveCount[$dept['id']] ?? 0;
        }

        $recentLogs = $this->activityLog->getRecentLogs(10);
        foreach ($recentLogs as &$log) {
            $uid = $log['user_id'] ?? '';
            $logUser = $profileMap[$uid] ?? null;
            $log['user'] = $logUser ? ['full_name' => $logUser['full_name']] : ['full_name' => 'System'];
        }

        arsort($userLeaveCounts);
        $topUsers = [];
        $i = 0;
        foreach ($userLeaveCounts as $uid => $count) {
            if ($i >= 5) break;
            $userProfile = $profileMap[$uid] ?? null;
            if ($userProfile) {
                $userProfile['total_leaves'] = $count;
                $userProfile['department'] = $deptMap[$userProfile['department_id'] ?? ''] ?? ['nama' => '-'];
                $topUsers[] = $userProfile;
            }
            $i++;
        }

        return view('dashboard.index', compact(
            'totalUsers', 'totalDepartments', 'pendingCount', 'approvedCount', 'rejectedCount',
            'statusChart', 'monthlyTrend', 'departmentChart', 'recentLogs', 'topUsers'
        ));
    }

    // Menampilkan halaman daftar anggota tim (khusus untuk role manager)
    public function team()
    {
        $role = Session::get('user_role');
        if ($role !== 'manager') {
            return redirect()->route('dashboard');
        }

        $userId = Session::get('user_id');
        $profiles = $this->supabase->select('profiles', '*', ['id' => $userId]);
        $profile = !empty($profiles) ? $profiles[0] : null;
        $departmentId = $profile['department_id'] ?? null;

        $teamMembers = [];
        $departmentName = '-';
        if ($departmentId) {
            $dept = $this->supabase->selectSingle('departments', 'id', $departmentId);
            $departmentName = $dept['nama'] ?? '-';

            $teamMembers = $this->supabase->select('profiles', '*', [
                'department_id' => $departmentId,
                'is_active' => 'true',
            ]);

            $today = date('Y-m-d');
            $year = date('Y');
            $bucket = config('services.supabase.storage_bucket');
            foreach ($teamMembers as &$member) {
                $balances = $this->supabase->selectAdmin('leave_balances', '*', [
                    'user_id' => $member['id'],
                    'tahun' => $year,
                ]);
                $member['sisa_cuti'] = !empty($balances) ? $balances[0]['sisa'] : 0;

                $activeLeave = $this->supabase->selectAdvanced('leave_requests', [
                    'columns' => 'id,tanggal_mulai,tanggal_selesai,status',
                    'filters' => [
                        'user_id' => 'eq.' . $member['id'],
                        'status' => 'eq.disetujui',
                        'tanggal_mulai' => 'lte.' . $today,
                        'tanggal_selesai' => 'gte.' . $today,
                    ],
                ], null, true);
                $member['is_on_leave'] = !empty($activeLeave);

                if (!empty($member['profile_photo_url'])) {
                    $signed = $this->supabase->getSignedUrl($bucket, $member['profile_photo_url'], 604800);
                    if ($signed) $member['profile_photo_url'] = $signed;
                }
            }
            unset($member);

            usort($teamMembers, function ($a, $b) {
                $roleOrder = ['manager' => 0, 'karyawan' => 1, 'admin' => 2];
                return ($roleOrder[$a['role'] ?? 'karyawan'] ?? 1) - ($roleOrder[$b['role'] ?? 'karyawan'] ?? 1);
            });
        }

        return view('dashboard.team', compact('teamMembers', 'departmentName'));
    }

    // Menghitung data tren pengajuan cuti untuk 6 bulan terakhir
    protected function getMonthlyTrend(): array
    {
        $labels = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-{$i} months"));
            $monthEnd = date('Y-m-t', strtotime("-{$i} months"));
            $labels[] = date('M Y', strtotime("-{$i} months"));

            $monthLeaves = $this->supabase->selectAdvanced('leave_requests', [
                'columns' => 'id,created_at',
                'filters' => [
                    'created_at' => 'gte.' . $monthStart . 'T00:00:00',
                ],
            ], null, true);

            $upperBound = $monthEnd . 'T23:59:59';
            $count = 0;
            foreach ($monthLeaves as $leave) {
                if (($leave['created_at'] ?? '') <= $upperBound) {
                    $count++;
                }
            }

            $data[] = $count;
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
  