@extends('layouts.app')

@section('title', 'Pengajuan Karyawan')

@section('content')
<div class="page-header">
    <h1 class="page-title">Pengajuan Karyawan</h1>
    <p class="page-subtitle">Semua pengajuan cuti dari anggota tim Anda</p>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form action="{{ route('leave.employee-requests') }}" method="GET" class="row g-2 align-items-end">
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
                <button type="submit" class="btn btn-sm btn-primary"><span class="material-symbols-outlined me-1">search</span>Filter</button>
                <a href="{{ route('leave.employee-requests') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
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
                        <th>Status</th>
                        <th>Diajukan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusColors = ['pending' => 'warning', 'disetujui' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'secondary'];
                    @endphp
                    @forelse($leaveRequests as $i => $leave)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $leave['user']['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($leave['user']['full_name'] ?? 'U') }}"
                                     alt="" class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                                <strong>{{ e($leave['user']['full_name'] ?? '-') }}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info me-1">{{ $leave['leave_type']['kode'] ?? '-' }}</span>
                            {{ $leave['leave_type']['nama'] ?? '-' }}
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M Y') }}
                            <br><small class="text-muted">s/d {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}</small>
                        </td>
                        <td><span class="badge bg-primary">{{ $leave['total_hari'] ?? 0 }}</span></td>
                        <td><span class="badge bg-{{ $statusColors[$leave['status'] ?? ''] ?? 'secondary' }}">{{ ucfirst($leave['status'] ?? '-') }}</span></td>
                        <td>{{ \Carbon\Carbon::parse($leave['created_at'])->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('leave.show', $leave['id']) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                <span class="material-symbols-outlined">visibility</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-2 mb-0">Belum ada pengajuan dari karyawan</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
  