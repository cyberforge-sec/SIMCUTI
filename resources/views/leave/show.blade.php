{{-- Tampilan antarmuka (UI) halaman show. --}}
@extends('layouts.app')

@section('title', 'Detail Pengajuan Cuti')

@section('content')
@php
    $statusColors = ['pending' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'secondary'];
@endphp

<div class="page-header">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h1 class="page-title">Detail Pengajuan Cuti</h1>
            <p class="page-subtitle">Informasi lengkap pengajuan cuti</p>
        </div>
        <a href="{{ route('leave.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Kembali
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><span class="material-symbols-outlined-text me-2">description</span>Informasi Pengajuan</span>
                <span class="badge bg-{{ $statusColors[$leave->status ?? ''] ?? 'secondary' }} fs-6">
                    {{ ucfirst($leave->status ?? '-') }}
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Nama Karyawan</div>
                    <div class="col-sm-8"><strong>{{ $leave->user['full_name'] ?? session('user_name') }}</strong></div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Jenis Cuti</div>
                    <div class="col-sm-8">
                        <span class="badge bg-info me-1">{{ $leave->leave_type['kode'] ?? '-' }}</span>
                        {{ $leave->leave_type['nama'] ?? '-' }}
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Tanggal Mulai</div>
                    <div class="col-sm-8">{{ \Carbon\Carbon::parse($leave->tanggal_mulai)->format('d F Y') }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Tanggal Selesai</div>
                    <div class="col-sm-8">{{ \Carbon\Carbon::parse($leave->tanggal_selesai)->format('d F Y') }}</div>
                </div>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Total Hari</div>
                    <div class="col-sm-8"><span class="badge bg-primary fs-6">{{ $leave->total_hari ?? 0 }} hari</span></div>
                </div>
                <hr>
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Alasan</div>
                    <div class="col-sm-8">{{ e($leave->alasan ?? '-') }}</div>
                </div>
                @if(!empty($leave->lampiran_url))
                <div class="row mb-3">
                    <div class="col-sm-4 text-muted">Lampiran</div>
                    <div class="col-sm-8">
                        @if($signedUrl)
                            <a href="{{ $signedUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                <span class="material-symbols-outlined me-1">download</span>Lihat / Unduh Lampiran
                            </a>
                        @else
                            <span class="text-muted">File tidak tersedia</span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if(!empty($leave->alasan_penolakan))
        <div class="card border-danger">
            <div class="card-header text-danger">
                <span class="material-symbols-outlined me-2">cancel</span>Alasan Penolakan
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $leave->alasan_penolakan }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Status Timeline -->
        <div class="card">
            <div class="card-header"><span class="material-symbols-outlined me-2">schedule</span>Status</div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Diajukan pada</small>
                    <div>{{ \Carbon\Carbon::parse($leave->created_at)->format('d M Y, H:i') }}</div>
                </div>
                @if($leave->status !== 'pending' && !empty($leave->tanggal_disetujui))
                <div class="mb-3">
                    <small class="text-muted">Diproses pada</small>
                    <div>{{ \Carbon\Carbon::parse($leave->tanggal_disetujui)->format('d M Y, H:i') }}</div>
                </div>
                @endif
                @if($approverName)
                <div class="mb-3">
                    <small class="text-muted">Diproses oleh</small>
                    <div><strong>{{ $approverName }}</strong></div>
                </div>
                @endif
                @if(!empty($leave->catatan_approval))
                <div class="mb-3">
                    <small class="text-muted">Catatan</small>
                    <div>{{ $leave->catatan_approval }}</div>
                </div>
                @endif
            </div>
        </div>

        <!-- Actions -->
        @if(($leave->status ?? '') === 'pending' && $leave->user_id === session('user_id'))
        <div class="card">
            <div class="card-body">
                <a href="{{ route('leave.edit', $leave->id) }}" class="btn btn-warning w-100 mb-2">
                    <span class="material-symbols-outlined me-2">edit</span>Edit Pengajuan
                </a>
                <button type="button" class="btn btn-outline-danger w-100" onclick="cancelLeave()">
                    <span class="material-symbols-outlined me-2">cancel</span>Batalkan
                </button>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function cancelLeave() {
    Swal.fire({
        title: 'Batalkan Pengajuan?',
        text: 'Tindakan ini tidak dapat dibatalkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ route("leave.cancel", $leave->id) }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', data.message, 'success');
                    setTimeout(() => location.href = '{{ route("leave.index") }}', 1000);
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}
</script>
@endpush
   