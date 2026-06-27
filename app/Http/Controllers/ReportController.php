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

    // Menginisialisasi class dan dependensi
    public function __construct(SupabaseService $supabase, ActivityLogService $activityLog)
    {
        // Simpan service supabase dan activity log yang disuntikkan
        $this->supabase = $supabase;
        $this->activityLog = $activityLog;
    }

    // Menampilkan halaman utama atau daftar data
    public function index(Request $request)
    {
        // Ambil info peran (role) dan ID departemen user dari sesi saat ini
        // Department scoping: managers only see their own department in the filter dropdown
        $role = Session::get('user_role');
        $userDepartmentId = Session::get('user_department_id');

        // Jika dia manager, batasi supaya dia cuma bisa lihat departemennya sendiri
        if ($role === 'manager' && $userDepartmentId) {
            $departments = $this->supabase->select('departments', 'id,nama', [
                'is_active' => 'true',
                'id' => $userDepartmentId,
            ]);
        } else {
            // Kalau bukan manager, ambil semua data departemen yang aktif
            $departments = $this->supabase->select('departments', 'id,nama', ['is_active' => 'true']);
        }

        // Ambil semua tipe cuti yang berstatus aktif
        $leaveTypes = $this->supabase->select('leave_types', 'id,nama', ['is_active' => 'true']);

        // Dapatkan data laporan yang sudah disaring sesuai filter
        $reports = $this->fetchFilteredReports($request);
        // Tambahkan informasi tambahan (seperti nama user) ke dalam laporan
        $this->enrichReports($reports);

        // Hitung total laporan berdasarkan masing-masing status
        $totalApproved = count(array_filter($reports, fn($r) => $r['status'] === 'disetujui'));
        $totalRejected = count(array_filter($reports, fn($r) => $r['status'] === 'ditolak'));
        $totalPending = count(array_filter($reports, fn($r) => $r['status'] === 'pending'));
        // Hitung total jumlah hari dari cuti-cuti yang disetujui
        $totalDays = array_sum(array_column(array_filter($reports, fn($r) => $r['status'] === 'disetujui'), 'total_hari'));

        // Kelompokkan data statistik buat dikirim ke halaman view
        $stats = compact('totalApproved', 'totalRejected', 'totalPending', 'totalDays');

        // Catat aktivitas kalau user ini melihat halaman laporan
        $this->activityLog->log('view', 'Melihat laporan cuti');

        // Tampilkan halaman index laporan beserta data yang dibutuhkan
        return view('reports.index', compact('reports', 'departments', 'leaveTypes', 'stats'));
    }

    // Fungsi untuk menangani proses export
    public function export(Request $request, string $format)
    {
        // Pastikan format eksport yang diminta bener-bener ada di list (csv, excel, atau pdf)
        if (!in_array($format, ['csv', 'excel', 'pdf'])) {
            return back()->withErrors(['error' => 'Format tidak valid.']);
        }

        // Ambil data laporan buat di-eksport (dibatasi 1000 baris) lalu lengkapi isinya
        $reports = $this->fetchFilteredReports($request, 1000);
        $this->enrichReports($reports);

        // Catat aktivitas eksport
        $this->activityLog->log('export', "Export laporan cuti ({$format})");

        // Panggil fungsi eksport yang sesuai sama format pilihan user
        if ($format === 'csv') {
            return $this->exportCSV($reports);
        }

        if ($format === 'excel') {
            return $this->exportExcel($reports);
        }

        return $this->exportPDF($reports);
    }

    // Fungsi untuk menangani proses buildFilters
    protected function buildFilters(Request $request): array
    {
        $filters = [];
        // Ambil lagi data peran dan departemen dari sesi
        $role = Session::get('user_role');
        $userDepartmentId = Session::get('user_department_id');

        // Whitelist valid status values to prevent PostgREST filter injection
        // Pastikan nilai filter status beneran ada, supaya nggak terjadi injection aneh-aneh
        $allowedStatuses = ['pending', 'disetujui', 'ditolak', 'dibatalkan'];
        if ($request->filled('status') && in_array($request->status, $allowedStatuses, true)) {
            $filters['status'] = 'eq.' . $request->status;
        }

        // Department scoping: enforce department boundary based on role
        // Mulai cek batasan filter buat departemen
        $effectiveDepartmentId = null;
        if ($role === 'manager') {
            // Managers can ONLY see their own department — ignore query string parameter
            // Kalau manager, paksakan filter ke departemennya aja. Parameter di URL dihiraukan.
            $effectiveDepartmentId = $userDepartmentId;
        } elseif ($role === 'admin' && $request->filled('department_id')) {
            // Admins can filter by any department, but validate UUID format strictly
            // Kalau admin, dia bebas milih filter. Tapi kita validasi bentuk id departemen (harus UUID)
            $deptId = $request->department_id;
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $deptId)) {
                // Verify department exists and is active
                // Pastikan id departemen itu emang ada di database dan masih aktif
                $dept = $this->supabase->selectSingle('departments', 'id', $deptId, 'id,is_active');
                if ($dept && ($dept['is_active'] ?? false)) {
                    $effectiveDepartmentId = $deptId;
                }
            }
        }

        // Kalau ada filter departemen yang akan dipakai
        if ($effectiveDepartmentId) {
            // Use admin key to look up department members (profiles RLS may block anon key)
            // Cari profil user siapa aja yang tergabung di departemen itu
            $deptUsers = $this->supabase->selectAdmin('profiles', 'id', ['department_id' => $effectiveDepartmentId, 'is_active' => 'true']);
            $userIds = array_column($deptUsers, 'id');
            // Kalau departemennya ada anggota, pasang filter user_id dari daftar anggota tersebut
            if (!empty($userIds)) {
                $filters['user_id'] = 'in.(' . implode(',', $userIds) . ')';
            } else {
                // Department exists but has no members — return empty result set
                // Kalau departemennya kosong tak berpenghuni, filter sengaja dibuat nyari id yang gak valid, biar hasilnya kosong
                $filters['user_id'] = 'eq.00000000-0000-0000-0000-000000000000';
            }
        }
        
        // Filter buat tipe cuti, divalidasi juga (harus UUID yang bener dan aktif)
        if ($request->filled('leave_type_id')) {
            // Validasi format ID
            $ltId = $request->leave_type_id;
            if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $ltId)) {
                // Verify leave type exists and is active
                $lt = $this->supabase->selectSingle('leave_types', 'id', $ltId, 'id,is_active');
                if ($lt && ($lt['is_active'] ?? false)) {
                    $filters['leave_type_id'] = 'eq.' . $ltId;
                }
            }
        }
        
        // Filter rentang tanggal dari (mulai cuti)
        if ($request->filled('date_from')) {
            // Validasi format tanggal
            $dateFrom = $request->date_from;
            // Pastikan format dan tanggalnya valid sebelum pasang filter gte (lebih besar sama dengan)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom) && $this->isValidDate($dateFrom)) {
                $filters['tanggal_mulai'] = 'gte.' . $dateFrom;
            }
        }
        
        // Filter rentang tanggal sampai (akhir cuti)
        if ($request->filled('date_to')) {
            // Validasi format tanggal
            $dateTo = $request->date_to;
            // Pastikan format dan tanggalnya valid sebelum pasang filter lte (lebih kecil sama dengan)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo) && $this->isValidDate($dateTo)) {
                $filters['tanggal_selesai'] = 'lte.' . $dateTo;
            }
        }

        return $filters;
    }

    /**
     * Validate date string is a real calendar date
     */
    protected function isValidDate(string $date): bool
    {
        // Cek apakah tanggal yang dimasukkan benar-benar ada di kalender (misal hindari 30 Februari)
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    // Fungsi untuk menangani proses fetchFilteredReports
    protected function fetchFilteredReports(Request $request, int $limit = 200): array
    {
        // Tarik data laporan pengajuan cuti dari Supabase berdasarkan filter yang udah disusun di atas
        return $this->supabase->selectAdvanced('leave_requests', [
            'columns' => '*',
            'filters' => $this->buildFilters($request),
            'order' => 'created_at.desc',
            'limit' => $limit,
        ], null, true);
    }

    // Fungsi untuk menangani proses enrichReports
    protected function enrichReports(array &$reports): void
    {
        // Siapkan array cache supaya nggak ngambil data user/departemen/tipe yang sama berkali-kali ke database
        $profileCache = [];
        $deptCache = [];
        $typeCache = [];

        foreach ($reports as &$r) {
            $uid = $r['user_id'] ?? '';
            // Kalau profil user belum di-cache, ambil dulu dari database
            if (!isset($profileCache[$uid])) {
                $profiles = $this->supabase->select('profiles', 'full_name,department_id', ['id' => $uid]);
                $profileCache[$uid] = !empty($profiles) ? $profiles[0] : ['full_name' => '-', 'department_id' => null];
            }
            // Tempelkan info nama user ke dalam laporan
            $r['user_name'] = $profileCache[$uid]['full_name'];

            $did = $profileCache[$uid]['department_id'] ?? '';
            // Kalau nama departemen belum di-cache, ambil juga dulu
            if ($did && !isset($deptCache[$did])) {
                $depts = $this->supabase->select('departments', 'nama', ['id' => $did]);
                $deptCache[$did] = !empty($depts) ? $depts[0]['nama'] : '-';
            }
            // Tempelkan info nama departemen
            $r['department_name'] = $did ? ($deptCache[$did] ?? '-') : '-';

            $tid = $r['leave_type_id'] ?? '';
            // Kalau nama tipe cuti belum di-cache, ambil dari db
            if (!isset($typeCache[$tid])) {
                $types = $this->supabase->select('leave_types', 'nama', ['id' => $tid]);
                $typeCache[$tid] = !empty($types) ? $types[0]['nama'] : '-';
            }
            // Tempelkan info nama tipe cuti
            $r['leave_type_name'] = $typeCache[$tid];
        }
    }

    /**
     * Sanitize a field value for CSV export to prevent formula injection.
     * Prefixes values starting with =, +, -, @, \t, \r with a single quote.
     */
    protected function sanitizeCsvField($value): string
    {
        // Kasih kutip satu supaya file CSV nggak nge-run data yang mirip rumus formula excel
        $value = (string) ($value ?? '-');
        if (preg_match('/^[=+\-@\t\r]/', $value)) {
            return "'" . $value;
        }
        return $value;
    }

    // Fungsi untuk menangani proses exportCSV
    protected function exportCSV(array $reports)
    {
        // Bikin nama file csv lengkap dengan tanggal hari ini
        $fileName = 'laporan_cuti_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        // Buat logic stream buat nulis isi data CSV secara langsung ke output browser
        $callback = function () use ($reports) {
            $output = fopen('php://output', 'w');
            // BOM for UTF-8
            // Tulis flag khusus (BOM) biar bisa dibaca Excel sebagai UTF-8
            fwrite($output, "\xEF\xBB\xBF");

            // Tulis barisan judul-judul kolom paling atas
            fputcsv($output, ['No', 'Nama Karyawan', 'Jenis Cuti', 'Tanggal Mulai', 'Tanggal Selesai', 'Total Hari', 'Status', 'Alasan']);

            // Tulis tiap-tiap baris datanya ke file
            foreach ($reports as $i => $r) {
                fputcsv($output, [
                    $i + 1,
                    $this->sanitizeCsvField($r['user_name'] ?? '-'),
                    $this->sanitizeCsvField($r['leave_type_name'] ?? '-'),
                    $r['tanggal_mulai'] ?? '-',
                    $r['tanggal_selesai'] ?? '-',
                    $r['total_hari'] ?? 0,
                    ucfirst($r['status'] ?? '-'),
                    $this->sanitizeCsvField($r['alasan'] ?? '-'),
                ]);
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Fungsi untuk menangani proses exportExcel
    protected function exportExcel(array $reports)
    {
        // Simple XLS (HTML table format for compatibility)
        // Bikin nama file Excel-nya
        $fileName = 'laporan_cuti_' . date('Y-m-d') . '.xls';

        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        // Rangkai tag tabel HTML, nanti Excel bakalan bisa baca format sederhana kayak gini
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<table border="1">';
        // Baris kolom header
        $html .= '<tr><th>No</th><th>Nama Karyawan</th><th>Jenis Cuti</th><th>Tanggal Mulai</th><th>Tanggal Selesai</th><th>Total Hari</th><th>Status</th><th>Alasan</th></tr>';

        // Masukin datanya baris-per-baris
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

    // Fungsi untuk menangani proses exportPDF
    protected function exportPDF(array $reports)
    {
        // Build HTML for PDF
        // Siapkan struktur dasar HTML buat nampilin kop surat dan styling tabel PDF
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

        // Loop buat ngisi data tabel
        foreach ($reports as $i => $r) {
            // Kasih warna berbeda buat setiap status
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

        // Mengecek modul PDF
        // Kalau kebetulan plugin dompdf dipasang, jadikan file PDF betulan
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            return $pdf->download('laporan_cuti_' . date('Y-m-d') . '.pdf');
        }

        // Fallback: download as HTML file
        // Tapi kalau nggak ada plugin-nya, ya udah download jadi file HTML aja sbg alternatif
        $fileName = 'laporan_cuti_' . date('Y-m-d') . '.html';
        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
   