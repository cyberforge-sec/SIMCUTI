@extends('layouts.app')

@section('title', 'Riwayat Cuti')

@section('content')
<div class="page-header">
    <h1 class="page-title">Riwayat Cuti</h1>
    <p class="page-subtitle">Riwayat semua pengajuan cuti yang telah diproses</p>
</div>

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
                        <th>Diproses</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $i => $leave)
                    @php
                        $statusColors = ['disetujui' => 'success', 'ditolak' => 'danger', 'dibatalkan' => 'secondary'];
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
                        <td>
                            @if(!empty($leave['tanggal_disetujui']))
                                {{ \Carbon\Carbon::parse($leave['tanggal_disetujui'])->format('d M Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('leave.show', $leave['id']) }}" class="btn btn-sm btn-outline-primary" title="Detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-archive" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-2 mb-0">Belum ada riwayat cuti</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
