@extends('layouts.app')

@section('title', 'Laporan Cuti')

@section('content')
<div class="page-header">
    <h1 class="page-title">Laporan Cuti</h1>
    <p class="page-subtitle">Generate dan export laporan pengajuan cuti</p>
</div>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #10B981, #059669);">
            <div class="stats-icon">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="stats-value">{{ $stats['totalApproved'] ?? 0 }}</div>
            <div class="stats-label">Disetujui</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
            <div class="stats-icon">
                <i class="bi bi-clock"></i>
            </div>
            <div class="stats-value">{{ $stats['totalPending'] ?? 0 }}</div>
            <div class="stats-label">Pending</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
            <div class="stats-icon">
                <i class="bi bi-x-circle"></i>
            </div>
            <div class="stats-value">{{ $stats['totalRejected'] ?? 0 }}</div>
            <div class="stats-label">Ditolak</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stats-card" style="background: linear-gradient(135deg, #3B82F6, #2563EB);">
            <div class="stats-icon">
                <i class="bi bi-calendar-check"></i>
            </div>
            <div class="stats-value">{{ $stats['totalDays'] ?? 0 }}</div>
            <div class="stats-label">Hari Terpakai</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>Filter Laporan
    </div>
    <div class="card-body">
        <form action="{{ route('reports.index') }}" method="GET" id="filterForm">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jenis Cuti</label>
                    <select name="leave_type_id" class="form-select">
                        <option value="">Semua Jenis</option>
                        @foreach($leaveTypes ?? [] as $lt)
                        <option value="{{ $lt['id'] }}" {{ request('leave_type_id') === $lt['id'] ? 'selected' : '' }}>{{ $lt['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Dari Tanggal</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai Tanggal</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
            </div>
            @if(session('user_role') === 'admin')
            <div class="row g-3 mt-3">
                <div class="col-md-3">
                    <label class="form-label">Departemen</label>
                    <select name="department_id" class="form-select">
                        <option value="">Semua Departemen</option>
                        @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept['id'] }}" {{ request('department_id') === $dept['id'] ? 'selected' : '' }}>{{ $dept['nama'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search me-2"></i>Tampilkan
                </button>
                <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-clockwise me-2"></i>Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Export Buttons -->
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1"><i class="bi bi-download me-2"></i>Export Laporan</h6>
                <small class="text-muted">Download laporan dalam format yang Anda inginkan</small>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('reports.export', ['format' => 'csv'] + request()->query()) }}" class="btn btn-sm btn-success">
                    <i class="bi bi-filetype-csv me-1"></i>CSV
                </a>
                <a href="{{ route('reports.export', ['format' => 'excel'] + request()->query()) }}" class="btn btn-sm btn-primary">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a href="{{ route('reports.export', ['format' => 'pdf'] + request()->query()) }}" class="btn btn-sm btn-danger">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-table me-2"></i>Data Laporan
        </div>
        <span class="badge bg-primary">{{ count($reports) }} data</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Karyawan</th>
                        <th>Departemen</th>
                        <th>Jenis Cuti</th>
                        <th>Tanggal Mulai</th>
                        <th>Tanggal Selesai</th>
                        <th style="width: 80px;">Hari</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusColors = [
                            'pending' => 'warning',
                            'disetujui' => 'success',
                            'ditolak' => 'danger',
                            'dibatalkan' => 'secondary'
                        ];
                        $statusIcons = [
                            'pending' => 'bi-clock',
                            'disetujui' => 'bi-check-circle',
                            'ditolak' => 'bi-x-circle',
                            'dibatalkan' => 'bi-slash-circle'
                        ];
                    @endphp
                    @forelse($reports as $i => $r)
                    <tr>
                        <td class="text-muted">{{ $i + 1 }}</td>
                        <td>
                            <div class="fw-semibold">{{ $r['user_name'] ?? '-' }}</div>
                        </td>
                        <td>{{ $r['department_name'] ?? '-' }}</td>
                        <td>{{ $r['leave_type_name'] ?? '-' }}</td>
                        <td>{{ $r['tanggal_mulai'] ?? '-' }}</td>
                        <td>{{ $r['tanggal_selesai'] ?? '-' }}</td>
                        <td>
                            <span class="badge bg-info">{{ $r['total_hari'] ?? 0 }}</span>
                        </td>
                        <td>
                            @php
                                $status = $r['status'] ?? '';
                                $color = $statusColors[$status] ?? 'secondary';
                                $icon = $statusIcons[$status] ?? 'bi-circle';
                            @endphp
                            <span class="badge bg-{{ $color }}">
                                <i class="bi {{ $icon }} me-1"></i>{{ ucfirst($status ?: '-') }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: #d1d5db;"></i>
                                <h6 class="mt-3 mb-2 text-muted">Tidak Ada Data</h6>
                                <p class="text-muted mb-0">Belum ada laporan yang sesuai dengan filter yang dipilih</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table thead th {
        background: #f9fafb;
        border-bottom: 2px solid #e5e7eb;
        font-weight: 600;
        font-size: 0.875rem;
        color: #374151;
        padding: 1rem 0.75rem;
    }
    .table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border-bottom: 1px solid #f3f4f6;
    }
    .table-hover tbody tr:hover {
        background: #f9fafb;
    }
    .badge {
        font-weight: 500;
        padding: 0.4rem 0.75rem;
        font-size: 0.8125rem;
    }
</style>
@endpush
