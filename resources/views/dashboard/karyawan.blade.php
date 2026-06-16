<!-- Welcome Banner -->
<div class="welcome-banner">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h2>Halo, {{ session('user_name') }}!</h2>
            <p>Kelola cuti Anda dengan mudah. Sisa cuti tahun ini: <strong>{{ $leaveBalance->sisa ?? 0 }} hari</strong></p>
        </div>
        <a href="{{ route('leave.create') }}" class="btn" style="background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(4px);">
            <i class="bi bi-plus-circle me-2"></i> Ajukan Cuti
        </a>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-3">
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #4F46E5, #4338CA);">
            <div class="stats-icon">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stats-value">{{ $leaveBalance->sisa ?? 0 }}</div>
            <div class="stats-label">Sisa Cuti</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #10B981, #059669);">
            <div class="stats-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stats-value">{{ $approvedCount ?? 0 }}</div>
            <div class="stats-label">Disetujui</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
            <div class="stats-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stats-value">{{ $pendingCount ?? 0 }}</div>
            <div class="stats-label">Menunggu</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
            <div class="stats-icon">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="stats-value">{{ $rejectedCount ?? 0 }}</div>
            <div class="stats-label">Ditolak</div>
        </div>
    </div>
</div>

<!-- Leave Balance + Quick Actions -->
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pie-chart me-2"></i> Saldo Cuti
            </div>
            <div class="card-body">
                <div class="balance-ring">
                    <div class="ring-value">{{ $leaveBalance->sisa ?? 0 }}</div>
                    <div class="ring-label">dari {{ $leaveBalance->total_jatah ?? 12 }} hari</div>
                </div>
                
                <div class="stat-mini">
                    <span class="stat-mini-label">Total Jatah</span>
                    <span class="stat-mini-value">{{ $leaveBalance->total_jatah ?? 12 }} hari</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Sudah Digunakan</span>
                    <span class="stat-mini-value text-danger">{{ $leaveBalance->terpakai ?? 0 }} hari</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Sisa Tersedia</span>
                    <span class="stat-mini-value text-success">{{ $leaveBalance->sisa ?? 0 }} hari</span>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i> Aksi Cepat
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="{{ route('leave.create') }}" class="quick-action-btn">
                        <div class="qa-icon purple"><i class="bi bi-plus-circle"></i></div>
                        <span>Ajukan Cuti Baru</span>
                    </a>
                    <a href="{{ route('leave.index') }}" class="quick-action-btn">
                        <div class="qa-icon blue"><i class="bi bi-list-ul"></i></div>
                        <span>Pengajuan Saya</span>
                    </a>
                    <a href="{{ route('leave.history') }}" class="quick-action-btn">
                        <div class="qa-icon green"><i class="bi bi-archive"></i></div>
                        <span>Riwayat Cuti</span>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="quick-action-btn">
                        <div class="qa-icon orange"><i class="bi bi-person-gear"></i></div>
                        <span>Edit Profil</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Leave Requests -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-clock-history me-2"></i> Pengajuan Terbaru
        </div>
        <a href="{{ route('leave.index') }}" class="btn btn-sm btn-outline-primary">
            Lihat Semua
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Durasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLeaves ?? [] as $leave)
                    <tr>
                        <td>
                            <strong>{{ $leave['leave_type']['nama'] ?? '-' }}</strong>
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M Y') }} —
                            {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $leave['total_hari'] }} hari</span>
                        </td>
                        <td>
                            @if($leave['status'] === 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($leave['status'] === 'disetujui')
                                <span class="badge bg-success">Disetujui</span>
                            @elseif($leave['status'] === 'ditolak')
                                <span class="badge bg-danger">Ditolak</span>
                            @else
                                <span class="badge bg-secondary">Dibatalkan</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('leave.show', $leave['id']) }}"
                                   class="btn btn-info" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($leave['status'] === 'pending')
                                <button onclick="cancelLeave('{{ $leave['id'] }}')"
                                        class="btn btn-danger" title="Batalkan">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Belum ada pengajuan cuti</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function cancelLeave(id) {
        Swal.fire({
            title: 'Batalkan Pengajuan?',
            text: 'Pengajuan cuti yang dibatalkan tidak dapat dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Batalkan',
            cancelButtonText: 'Tidak'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/leave/${id}/cancel`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil!', data.message, 'success')
                            .then(() => location.reload());
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
