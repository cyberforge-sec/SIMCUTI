@extends('layouts.app')

@section('title', 'Anggota Tim')

@section('content')
<div class="page-header">
    <h1 class="page-title">Anggota Tim</h1>
    <p class="page-subtitle">Departemen: <strong>{{ $departmentName }}</strong> &middot; {{ count($teamMembers) }} anggota</p>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Anggota</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Sisa Cuti</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($teamMembers as $i => $member)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $member['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($member['full_name'] ?? 'U') . '&size=32&background=4F46E5&color=fff' }}"
                                     alt="" class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                                <strong>{{ $member['full_name'] ?? '-' }}</strong>
                            </div>
                        </td>
                        <td>{{ $member['email'] ?? '-' }}</td>
                        <td><span class="badge bg-primary">{{ ucfirst($member['role'] ?? '-') }}</span></td>
                        <td>
                            <span class="badge {{ ($member['sisa_cuti'] ?? 0) > 3 ? 'bg-success' : 'bg-warning' }}">
                                {{ $member['sisa_cuti'] ?? 0 }} hari
                            </span>
                        </td>
                        <td>
                            @if($member['is_on_leave'] ?? false)
                                <span class="badge bg-danger"><i class="bi bi-calendar-x me-1"></i>Sedang Cuti</span>
                            @else
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktif</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-people" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-2 mb-0">Belum ada anggota tim di departemen ini</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
