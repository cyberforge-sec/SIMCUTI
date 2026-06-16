<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ReportController extends Controller
{
    protected SupabaseService $supabase;
    protected ActivityLogService $activityLog;

    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    public function index(Request $request)
    {
        $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        $leaveTypes = $this->supabase->select('leave_types', 'id,nama', ['is_active' => 'true']);

        $reports = $this->fetchFilteredReports($request);
        $this->enrichReports($reports);

        $totalApproved = count(array_filter($reports, fn($r) => $r['status'] === 'disetujui'));
        $totalRejected = count(array_filter($reports, fn($r) => $r['status'] === 'ditolak'));
        $totalPending = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));
        $totalDays = array_sum(array_column(array_filter($reports, fn($r) => $r['status'] === 'disetujui'), 'total_hari'));

        $stats = compact('totalApproved', 'totalRejected', 'totalPending', 'totalDays');

        $this->activityLog->log('view', 'Melihat laporan cuti');

        return view('reports.index', compact('reports', 'departments', 'leaveTypes', 'stats'));
    }

    public function export(Request $request, string $format)
    {
        if (!in_array($format, ['csv', 'excel', 'pdf'])) {
            return back()->withErrors(['error' => 'Format tidak valid.']);
        }

        $reports = $this->fetchFilteredReports($request, 1000);
        $this->enrichReports($reports);

        $this->activityLog->log('export', "Export laporan cuti ({$format})");

        if ($format === 'csv') {
            return $this->exportCSV($reports);
        }

        if ($format === 'excel') {
            return $this->exportExcel($reports);
        }

        return $this->exportPDF($reports);
    }

    protected function buildFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('status')) {
            $filters['status'] = 'eq.' . $request->status;
        }
        if ($request->filled('department_id')) {
            $deptUsers = $this->supabase->select('profiles', 'id', ['department_id' => $request->department_id]);
            $userIds = array_column($deptUsers, 'id');
            if (!empty($userIds)) {
                $filters['user_id'] = $userIds;
            }
        }
        if ($request->filled('leave_type_id')) {
            $filters['leave_type_id'] = 'eq.' . $request->leave_type_id;
        }
        if ($request->filled('date_from')) {
            $filters['tanggal_mulai'] = 'gte.' . $request->date_from;
        }
        if ($request->filled('date_to')) {
            $filters['tanggal_selesai'] = 'lte.' . $request->date_to;
        }

        return $filters;
    }

    protected function fetchFilteredReports(Request $request, int $limit = 200): array
    {
        return $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => $this->buildFilters($request),
            'order' => 'created_at.desc',
            'limit' => $limit,
        ], null, true);
    }

    protected function enrichReports(array &$reports): void
    {
        $profileCache = [];
        $deptCache = [];
        $typeCache = [];

        foreach ($reports as &$r) {
            $uid = $r['user_id'] ?? '';
            if (!isset($profileCache[$uid])) {
                $profiles = $this->supabase->select('profiles', 'full_name,department_id', ['id' => $uid]);
                $profileCache[$uid] = !empty($profiles) ? $profiles[0] : ['full_name' => '-', 'department_id' => null];
            }
            $r['user_name'] = $profileCache[$uid]['full_name'];

            $did = $profileCache[$uid]['department_id'] ?? '';
            if ($did && !isset($deptCache[$did])) {
                $depts = $this->supabase->select('departments', 'nama', ['id' => $did]);
                $deptCache[$did] = !empty($depts) ? $depts[0]['nama'] : '-';
            }
            $r['department_name'] = $did ? ($deptCache[$did] ?? '-') : '-';

            $tid = $r['leave_type_id'] ?? '';
            if (!isset($typeCache[$tid])) {
                $types = $this->supabase->select('leave_types', 'nama', ['id' => $tid]);
                $typeCache[$tid] = !empty($types) ? $types[0]['nama'] : '-';
            }
            $r['leave_type_name'] = $typeCache[$tid];
        }
    }

    protected function exportCSV(array $reports)
    {
        $fileName = 'laporan_cuti_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($reports) {
            $output = fopen('php://output', 'w');
            // BOM for UTF-8
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['No', 'Nama Karyawan', 'Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Total Hari', 'Status', 'Alasan']);

            foreach ($reports as $i => $r) {
                fputcsv($output, [
                    $i + 1,
                    $r['user_name'] ?? '-',
                    $r['leave_type_name'] ?? '-',
                    $r['tanggal_mulai'] ?? '-',
                    $r['tanggal_selesai'] ?? '-',
                    $r['total_hari'] ?? 0,
                    ucfirst($r['status'] ?? '-'),
                    $r['alasan'] ?? '-',
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function exportExcel(array $reports)
    {
        // Simple XLS (HTML table format for compatibility)
        $fileName = 'laporan_cuti_' . date('Y-m-d') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1">';
        $html .= '<tr><th>No</th><th>Nama Karyawan</th><th>Jenis Cuti</th><th>Tanggal Mulai</th><th>Tanggal Selesai</th><th>Total Hari</th><th>Status</th><th>Alasan</th></tr>';

        foreach ($reports as $i => $r) {
            $html .= '<tr>';
            $html .= '<td>' . ($i + 1) . '</td>';
            $html .= '<td>' . htmlspecialchars($r['user_name'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($r['leave_type_name'] ?? '-') . '</td>';
            $html .= '<td>' . ($r['tanggal_mulai'] ?? '-') . '</td>';
            $html .= '<td>' . ($r['tanggal_selesai'] ?? '-') . '</td>';
            $html .= '<td>' . ($r['total_hari'] ?? 0) . '</td>';
            $html .= '<td>' . ucfirst($r['status'] ?? '-') . '</td>';
            $html .= '<td>' . htmlspecialchars($r['alasan'] ?? '-') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table></body></html>';

        return response($html, 200, $headers);
    }

    protected function exportPDF(array $reports)
    {
        // Build HTML for PDF
        $html = '
        <html>
        <head>
            <style>
                body { font-family: sans-serif; font-size: 11px; }
                h2 { text-align: center; margin-bottom: 5px; }
                .subtitle { text-align: center; color: #666; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th { background: #4F46E5; color: white; padding: 8px 5px; text-align: left; font-size: 10px; }
                td { padding: 6px 5px; border-bottom: 1px solid #ddd; font-size: 10px; }
                tr:nth-child(even) { background: #f9f9f9; }
                .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <h2>Laporan Pengajuan Cuti</h2>
            <p class="subtitle">SIMCUTI - Sistem Informasi Manajemen Cuti<br>Dicetak: ' . now()->format('d F Y, H:i') . '</p>
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Jenis Cuti</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Hari</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($reports as $i => $r) {
            $statusColor = match ($r['status']) {
                'disetujui' => '#10B981',
                'ditolak' => '#EF4444',
                'pending' => '#F59E0B',
                default => '#6B7280',
            };
            $html .= '<tr>
                <td>' . ($i + 1) . '</td>
                <td>' . htmlspecialchars($r['user_name'] ?? '-') . '</td>
                <td>' . htmlspecialchars($r['leave_type_name'] ?? '-') . '</td>
                <td>' . ($r['tanggal_mulai'] ?? '-') . '</td>
                <td>' . ($r['tanggal_selesai'] ?? '-') . '</td>
                <td>' . ($r['total_hari'] ?? 0) . '</td>
                <td style="color:' . $statusColor . ';font-weight:bold;">' . ucfirst($r['status'] ?? '-') . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<div class="footer">Total data: ' . count($reports) . ' pengajuan</div>';
        $html .= '</body></html>';

        // Check if dompdf is available
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('laporan_cuti_' . date('Y-m-d') . '.pdf');
        }

        // Fallback: download as HTML file
        $fileName = 'laporan_cuti_' . date('Y-m-d') . '.html';
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
