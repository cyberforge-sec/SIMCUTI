<!-- Stats Cards Row 1 -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #4F46E5, #4338CA);">
            <div class="stats-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stats-value">{{ $totalUsers ?? 0 }}</div>
            <div class="stats-label">Total Pengguna</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #10B981, #059669);">
            <div class="stats-icon">
                <i class="bi bi-building"></i>
            </div>
            <div class="stats-value">{{ $totalDepartments ?? 0 }}</div>
            <div class="stats-label">Departemen</div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card stats-card" style="background: linear-gradient(135deg, #3B82F6, #2563EB);">
            <div class="stats-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stats-value">{{ $approvedCount ?? 0 }}</div>
            <div class="stats-label">Disetujui</div>
        </div>
    </div>
</div>

<!-- Stats Cards Row 2 -->
<div class="row g-3 mt-0">
    <div class="col-md-6">
        <div class="card stats-card" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
            <div class="stats-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stats-value">{{ $pendingCount ?? 0 }}</div>
            <div class="stats-label">Pending Approval</div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card stats-card" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
            <div class="stats-icon">
                <i class="bi bi-calendar-x"></i>
            </div>
            <div class="stats-value">{{ $rejectedCount ?? 0 }}</div>
            <div class="stats-label">Ditolak Bulan Ini</div>
        </div>
    </div>
</div>

<!-- Trend Chart -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up me-2"></i> Trend Pengajuan (6 Bulan Terakhir)
            </div>
            <div class="card-body">
                <div style="height: 300px;">
                    <canvas id="trendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Logs -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-activity me-2"></i> Aktivitas Terbaru
        </div>
        <a href="{{ route('activity-logs.index') }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua
        </a>
    </div>
    <div class="card-body">
        @forelse($recentLogs ?? [] as $log)
        @php
            $aksi = $log['aksi'] ?? '';
            $iconClass = match(true) {
                str_contains($aksi, 'login') || str_contains($aksi, 'logout') => 'act-login',
                str_contains($aksi, 'create') => 'act-create',
                str_contains($aksi, 'approve') => 'act-approve',
                str_contains($aksi, 'reject') => 'act-reject',
                str_contains($aksi, 'update') => 'act-update',
                str_contains($aksi, 'delete') => 'act-delete',
                default => 'act-default',
            };
        @endphp
        <div class="activity-item">
            <div class="activity-icon {{ $iconClass }}">
                @if($log['aksi'] === 'login')
                    <i class="bi bi-box-arrow-in-right"></i>
                @elseif($log['aksi'] === 'logout')
                    <i class="bi bi-box-arrow-right"></i>
                @elseif(str_contains($log['aksi'], 'create'))
                    <i class="bi bi-plus"></i>
                @elseif(str_contains($log['aksi'], 'update'))
                    <i class="bi bi-pencil"></i>
                @elseif(str_contains($log['aksi'], 'delete'))
                    <i class="bi bi-trash"></i>
                @elseif(str_contains($log['aksi'], 'approve'))
                    <i class="bi bi-check"></i>
                @elseif(str_contains($log['aksi'], 'reject'))
                    <i class="bi bi-x"></i>
                @else
                    <i class="bi bi-circle"></i>
                @endif
            </div>
            <div>
                <div class="activity-text">
                    <strong>{{ $log['user']['full_name'] ?? 'System' }}</strong>
                    — {{ $log['deskripsi'] }}
                </div>
                <div class="activity-time">
                    {{ \Carbon\Carbon::parse($log['created_at'])->diffForHumans() }}
                </div>
            </div>
        </div>
        @empty
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <p>Belum ada aktivitas</p>
        </div>
        @endforelse
    </div>
</div>
