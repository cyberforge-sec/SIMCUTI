<!-- Stats Cards -->
<div class="row">
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #4F46E5, #4338CA);">
            <div class="stats-icon">
                <i class="bi bi-people"></i>
            </div>
            <div class="stats-value">{{ $teamSize ?? 0 }}</div>
            <div class="stats-label">Anggota Tim</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
            <div class="stats-icon">
                <i class="bi bi-clock-history"></i>
            </div>
            <div class="stats-value">{{ $pendingCount ?? 0 }}</div>
            <div class="stats-label">Perlu Persetujuan</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
            <div class="stats-icon">
                <i class="bi bi-person-x"></i>
            </div>
            <div class="stats-value">{{ $onLeaveToday ?? 0 }}</div>
            <div class="stats-label">Cuti Hari Ini</div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #10B981, #059669);">
            <div class="stats-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stats-value">{{ $approvedThisMonth ?? 0 }}</div>
            <div class="stats-label">Disetujui Bulan Ini</div>
        </div>
    </div>
</div>

<!-- Pending Approvals + Sidebar -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="bi bi-hourglass-split me-2"></i> Menunggu Persetujuan
                </div>
                <a href="{{ route('leave.pending') }}" class="btn btn-sm btn-outline-primary">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body">
                @forelse($pendingApprovals ?? [] as $leave)
                <div class="approval-card">
                    <div class="user-info">
                        <img src="{{ $leave['user']['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($leave['user']['full_name']) }}" 
                             alt="Avatar">
                        <div>
                            <div class="user-name">{{ $leave['user']['full_name'] }}</div>
                            <div class="user-detail">
                                {{ $leave['leave_type']['nama'] }} · 
                                {{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M') }} — 
                                {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}
                                · <span class="badge bg-info" style="padding: 0.2rem 0.5rem; font-size: 0.7rem;">{{ $leave['total_hari'] }} hari</span>
                            </div>
                        </div>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button onclick="approveLeave('{{ $leave['id'] }}')" 
                                class="btn btn-success" title="Setujui">
                            <i class="bi bi-check"></i>
                        </button>
                        <button onclick="rejectLeave('{{ $leave['id'] }}')" 
                                class="btn btn-danger" title="Tolak">
                            <i class="bi bi-x"></i>
                        </button>
                        <a href="{{ route('leave.show', $leave['id']) }}" 
                           class="btn btn-info" title="Detail">
                            <i class="bi bi-eye"></i>
                        </a>
                    </div>
                </div>
                @empty
                <div class="empty-state">
                    <i class="bi bi-check-circle"></i>
                    <p>Tidak ada pengajuan yang perlu disetujui</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- Sidebar: Quick Stats + On Leave -->
    <div class="col-md-4">
        <!-- Quick Stats -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-speedometer2 me-2"></i> Statistik Tim
            </div>
            <div class="card-body">
                <div class="stat-mini">
                    <span class="stat-mini-label">Approval Rate</span>
                    <span class="stat-mini-value text-success">{{ $approvalRate ?? 0 }}%</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Rata-rata Approval</span>
                    <span class="stat-mini-value text-info">{{ $avgApprovalTime ?? 0 }} jam</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Total Pengajuan</span>
                    <span class="stat-mini-value text-primary">{{ $totalRequests ?? 0 }}</span>
                </div>
            </div>
        </div>
        
        <!-- Sedang Cuti Hari Ini -->
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-calendar-x me-2"></i> Sedang Cuti Hari Ini
            </div>
            <div class="card-body">
                @php
                    $onLeaveList = collect($teamMembers ?? [])->filter(fn($m) => $m['is_on_leave'] ?? false);
                @endphp
                @if($onLeaveList->count() > 0)
                    @foreach($onLeaveList as $member)
                    <div class="on-leave-item">
                        <img src="{{ $member['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($member['full_name']) }}"
                             alt="">
                        <div>
                            <div class="leave-name">{{ $member['full_name'] }}</div>
                            <div class="leave-detail">{{ $member['sisa_cuti'] }} hari sisa</div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="empty-state">
                        <i class="bi bi-check-circle"></i>
                        <p>Tidak ada anggota tim yang sedang cuti</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function approveLeave(id) {
        Swal.fire({
            title: 'Setujui Pengajuan?',
            text: 'Pengajuan cuti akan disetujui',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Catatan (opsional)',
            inputPlaceholder: 'Tambahkan catatan approval...',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/leave/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        catatan: result.value
                    })
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
    
    function rejectLeave(id) {
        Swal.fire({
            title: 'Tolak Pengajuan?',
            text: 'Harap berikan alasan penolakan',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan',
            inputPlaceholder: 'Jelaskan alasan penolakan...',
            inputValidator: (value) => {
                if (!value) {
                    return 'Alasan penolakan wajib diisi!';
                }
                if (value.length < 10) {
                    return 'Alasan minimal 10 karakter!';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Tolak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/leave/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        alasan: result.value
                    })
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
