@extends('layouts.app')

@section('title', 'Pengajuan Cuti Saya')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Pengajuan Cuti Saya</h1>
        <p class="page-subtitle">Daftar pengajuan cuti yang telah Anda ajukan</p>
    </div>
    <a href="{{ route('leave.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Ajukan Cuti Baru
    </a>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form action="{{ route('leave.index') }}" method="GET" class="row g-2 align-items-end">
            @if(request('q'))
                <input type="hidden" name="q" value="{{ request('q') }}">
            @endif
            <div class="col-md-4">
                <label class="form-label small">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search me-1"></i>Filter</button>
                <a href="{{ route('leave.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if(isset($searchQuery) && $searchQuery)
    <div class="alert alert-info py-2 mb-3">
        <i class="bi bi-search me-2"></i>Hasil pencarian: <strong>"{{ $searchQuery }}"</strong> — {{ count($leaveRequests) }} hasil ditemukan
        <a href="{{ route('leave.index') }}" class="ms-2 text-decoration-underline">Hapus pencarian</a>
    </div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal</th>
                        <th>Total Hari</th>
                        <th>Status</th>
                        <th>Diajukan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveRequests as $i => $leave)
                    @php
                        $statusColors = ['pending' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'secondary'];
                    @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $leave['leave_type']['nama'] ?? '-' }}</strong>
                            <br><small class="text-muted">{{ $leave['leave_type']['kode'] ?? '' }}</small>
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M Y') }}
                            <br><small class="text-muted">s/d {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}</small>
                        </td>
                        <td><span class="badge bg-info">{{ $leave['total_hari'] ?? 0 }} hari</span></td>
                        <td><span class="badge bg-{{ $statusColors[$leave['status'] ?? ''] ?? 'secondary' }}">{{ ucfirst($leave['status'] ?? '-') }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($leave['created_at'])->format('d M Y') }}</td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('leave.show', $leave['id']) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(($leave['status'] ?? '') === 'pending')
                                <a href="{{ route('leave.edit', $leave['id']) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Batalkan"
                                        onclick="cancelLeave('{{ $leave['id'] }}')">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            @if(isset($searchQuery) && $searchQuery)
                                <p class="mt-2 mb-0">Tidak ada hasil untuk "<strong>{{ $searchQuery }}</strong>"</p>
                                <a href="{{ route('leave.index') }}" class="small">Hapus pencarian</a>
                            @else
                                <p class="mt-2 mb-0">Belum ada pengajuan cuti</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function cancelLeave(id) {
    Swal.fire({
        title: 'Batalkan Pengajuan?',
        text: 'Pengajuan cuti yang dibatalkan tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/leave/${id}/cancel`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}
</script>
@endpush
