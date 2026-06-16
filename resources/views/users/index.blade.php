@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Pengguna</h1>
        <p class="page-subtitle">Kelola data pengguna sistem</p>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-primary">
        <i class="bi bi-person-plus me-2"></i>Tambah Pengguna
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Departemen</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $i => $user)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $user['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user['full_name'] ?? 'U') }}"
                                     alt="" class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                                <strong>{{ $user['full_name'] ?? '-' }}</strong>
                            </div>
                        </td>
                        <td>{{ $user['email'] ?? '-' }}</td>
                        <td>
                            @php
                                $roleColors = ['admin' => 'danger', 'manager' => 'warning', 'karyawan' => 'info'];
                            @endphp
                            <span class="badge bg-{{ $roleColors[$user['role'] ?? ''] ?? 'secondary' }}">{{ ucfirst($user['role'] ?? '-') }}</span>
                        </td>
                        <td>{{ $user['department_name'] ?? '-' }}</td>
                        <td>
                            @if(!empty($user['is_active']))
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('users.edit', $user['id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('users.toggle-active', $user['id']) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-{{ !empty($user['is_active']) ? 'warning' : 'success' }}" title="{{ !empty($user['is_active']) ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <i class="bi bi-{{ !empty($user['is_active']) ? 'person-x' : 'person-check' }}"></i>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser('{{ $user['id'] }}', '{{ $user['full_name'] }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-2 mb-0">Belum ada data pengguna</p>
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
function deleteUser(id, nama) {
    Swal.fire({
        title: 'Nonaktifkan User?',
        text: `User "${nama}" akan dinonaktifkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Ya!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/users/${id}`;
            form.innerHTML = `<input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="${csrfToken}">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
