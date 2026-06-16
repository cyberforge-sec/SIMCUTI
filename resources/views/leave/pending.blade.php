@extends('layouts.app')

@section('title', 'Perlu Persetujuan')

@section('content')
<div class="page-header">
    <h1 class="page-title">Perlu Persetujuan</h1>
    <p class="page-subtitle">Daftar pengajuan cuti yang menunggu keputusan Anda</p>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Karyawan</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Diajukan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingLeaves as $i => $leave)
                    <tr id="row-{{ $leave['id'] }}">
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $leave['user']['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($leave['user']['full_name'] ?? 'U') }}"
                                     alt="" class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                                <strong>{{ $leave['user']['full_name'] ?? '-' }}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info me-1">{{ $leave['leave_type']['kode'] ?? '-' }}</span>
                            {{ $leave['leave_type']['nama'] ?? '-' }}
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M') }} - {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}
                        </td>
                        <td><span class="badge bg-primary">{{ $leave['total_hari'] ?? 0 }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($leave['created_at'])->diffForHumans() }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('leave.show', $leave['id']) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-success" onclick="approveLeave('{{ $leave['id'] }}')" title="Setujui">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal('{{ $leave['id'] }}')" title="Tolak">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-check-circle" style="font-size: 3rem; color: #10B981;"></i>
                            <p class="mt-2 mb-0">Tidak ada pengajuan yang menunggu persetujuan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div x-data="{ show: false, rejectId: '', rejectReason: '' }"
     x-on:show-reject-modal.window="show = true; rejectId = $event.detail.id; rejectReason = ''"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center"
     style="display: none;">
    <div class="fixed inset-0 bg-black/50" @click="show = false"></div>
    <div x-show="show" x-transition
         class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 z-10">
        <div class="flex items-center justify-between px-6 py-4 border-b border-border-light">
            <h5 class="font-semibold text-lg text-dark">
                <i class="bi bi-x-circle text-danger me-2"></i>Tolak Pengajuan
            </h5>
            <button type="button" class="text-text-muted hover:text-dark bg-transparent border-none text-xl cursor-pointer" @click="show = false">&times;</button>
        </div>
        <div class="p-6">
            <div class="mb-3">
                <label for="rejectReason" class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
                <textarea class="form-control" x-model="rejectReason" id="rejectReason" rows="3" minlength="10" required
                          placeholder="Jelaskan alasan penolakan (minimal 10 karakter)..."></textarea>
            </div>
        </div>
        <div class="flex justify-end gap-2 px-6 py-4 border-t border-border-light">
            <button type="button" class="btn btn-secondary" @click="show = false">Batal</button>
            <button type="button" class="btn btn-danger" @click="
                if (rejectReason.length < 10) {
                    Swal.fire('Peringatan', 'Alasan penolakan minimal 10 karakter.', 'warning');
                    return;
                }
                fetch(`/leave/${rejectId}/reject`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ alasan: rejectReason })
                })
                .then(r => r.json())
                .then(data => {
                    show = false;
                    if (data.success) {
                        document.getElementById('row-' + rejectId).style.opacity = '0.5';
                        Swal.fire('Berhasil!', data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                });
            ">Tolak Pengajuan</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function approveLeave(id) {
    Swal.fire({
        title: 'Setujui Pengajuan?',
        text: 'Pastikan Anda telah memeriksa pengajuan dengan teliti.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/leave/${id}/approve`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('row-' + id).style.opacity = '0.5';
                    Swal.fire('Berhasil!', data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}

function showRejectModal(id) {
    window.dispatchEvent(new CustomEvent('show-reject-modal', { detail: { id } }));
}
</script>
@endpush
