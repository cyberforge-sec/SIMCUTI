@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="page-header">
    <h1 class="page-title">Profil Saya</h1>
    <p class="page-subtitle">Kelola informasi profil dan akun Anda</p>
</div>

<!-- Profile Header Card -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <img src="{{ session('profile_photo_url') ?? 'https://ui-avatars.com/api/?name=' . urlencode(session('user_name')) . '&size=80&background=4F46E5&color=fff' }}"
                 alt="Profile" class="rounded-circle" width="80" height="80" style="object-fit:cover; border: 3px solid var(--color-primary-light);">
            <div style="flex: 1; min-width: 200px;">
                <h5 style="margin-bottom: 0.25rem; font-weight: 700;">{{ session('user_name') }}</h5>
                <p class="text-muted" style="margin-bottom: 0.25rem; font-size: 0.875rem;">
                    {{ session('user_email') }}
                </p>
                <span class="badge bg-primary">{{ ucfirst(session('user_role')) }}</span>
            </div>
            <div>
                <form action="{{ route('profile.photo') }}" method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2" id="photoForm">
                    @csrf
                    <label for="photoInput" class="btn btn-sm btn-outline-primary" style="cursor: pointer; margin-bottom: 0;">
                        <i class="bi bi-camera me-1"></i>Ubah Foto
                    </label>
                    <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png"
                           style="display: none;" onchange="document.getElementById('photoForm').submit()">
                </form>
                @if(session('profile_photo_url'))
                <form action="{{ route('profile.photo.delete') }}" method="POST" class="mt-2">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" style="margin-bottom: 0;">
                        <i class="bi bi-trash me-1"></i>Hapus Foto
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Edit Profile Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>Informasi Pribadi
            </div>
            <div class="card-body">
                <form action="{{ route('profile.update') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
                        <div>
                            <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name"
                                   value="{{ old('full_name', $profile['full_name'] ?? '') }}" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label for="phone" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                                   value="{{ old('phone', $profile['phone'] ?? '') }}" maxlength="20" placeholder="08xxxxxxxxxx">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="{{ session('user_email') }}" disabled>
                            <small class="text-muted">Email tidak dapat diubah</small>
                        </div>

                        <div>
                            <label class="form-label">Departemen</label>
                            @if(session('user_role') === 'admin')
                            <select class="form-select" name="department_id">
                                <option value="">— Belum Ditentukan —</option>
                                @foreach($departments ?? [] as $dept)
                                    <option value="{{ $dept['id'] }}" {{ ($profile['department_id'] ?? '') === $dept['id'] ? 'selected' : '' }}>
                                        {{ $dept['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                            @else
                            <select class="form-select" disabled>
                                <option>—</option>
                                @foreach($departments ?? [] as $dept)
                                    @if(($profile['department_id'] ?? '') === $dept['id'])
                                        <option selected>{{ $dept['nama'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">Hubungi admin untuk mengubah</small>
                            @endif
                        </div>
                    </div>

                    <div style="margin-top: 1.5rem; padding-top: 1.25rem; border-top: 1px solid var(--color-border-light); display: flex; gap: 0.75rem; align-items: center;">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                        </button>
                        <button type="button" onclick="deleteProfile()" class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash me-1"></i>Hapus Akun
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="col-md-4">
        <!-- Account Info -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <i class="bi bi-shield me-2"></i>Informasi Akun
            </div>
            <div class="card-body">
                <div class="stat-mini">
                    <span class="stat-mini-label">User ID</span>
                    <span class="stat-mini-value" style="font-size: 0.7rem; font-family: monospace; word-break: break-all;">{{ substr(session('user_id'), 0, 8) }}...</span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Bergabung</span>
                    <span class="stat-mini-value" style="font-size: 0.875rem;">
                        {{ isset($profile['created_at']) ? \Carbon\Carbon::parse($profile['created_at'])->format('d M Y') : '-' }}
                    </span>
                </div>
                <div class="stat-mini">
                    <span class="stat-mini-label">Login Terakhir</span>
                    <span class="stat-mini-value" style="font-size: 0.875rem;">
                        {{ isset($profile['last_login_at']) ? \Carbon\Carbon::parse($profile['last_login_at'])->diffForHumans() : '-' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-lightning me-2"></i>Aksi Cepat
            </div>
            <div class="card-body">
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('settings') }}" class="quick-action-btn">
                        <div class="qa-icon purple"><i class="bi bi-gear"></i></div>
                        <span>Pengaturan Akun</span>
                    </a>
                    <a href="{{ route('settings') }}#password" class="quick-action-btn">
                        <div class="qa-icon orange"><i class="bi bi-key"></i></div>
                        <span>Ubah Password</span>
                    </a>
                    <a href="{{ route('leave.create') }}" class="quick-action-btn">
                        <div class="qa-icon blue"><i class="bi bi-plus-circle"></i></div>
                        <span>Ajukan Cuti</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function deleteProfile() {
        Swal.fire({
            title: 'Hapus Akun Permanen?',
            html: '<p style="margin-bottom: 1rem;">Semua data Anda akan dihapus permanen dan tidak dapat dikembalikan.</p>'
                + '<p style="margin-bottom: 0.5rem; font-weight: 600; text-align: left;">Ketik "HAPUS" untuk mengkonfirmasi:</p>'
                + '<input type="text" id="swal-confirm-text" class="swal2-input" placeholder="HAPUS" style="margin-bottom: 1rem;">'
                + '<p style="margin-bottom: 0.5rem; font-weight: 600; text-align: left;">Masukkan password Anda:</p>'
                + '<input type="password" id="swal-password" class="swal2-input" placeholder="Password" style="text-align: left;">',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                const confirmText = document.getElementById('swal-confirm-text').value;
                const password = document.getElementById('swal-password').value;
                if (confirmText !== 'HAPUS') {
                    Swal.showValidationMessage('Ketik HAPUS untuk mengkonfirmasi!');
                    return false;
                }
                if (!password) {
                    Swal.showValidationMessage('Password wajib diisi!');
                    return false;
                }
                return { password };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("profile.destroy") }}', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ password: result.value.password })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'Akun Dihapus', timer: 2000, showConfirmButton: false })
                            .then(() => window.location.href = '/login');
                    } else {
                        Swal.fire('Error!', data.message || 'Gagal menghapus akun.', 'error');
                    }
                })
                .catch(() => Swal.fire('Error!', 'Terjadi kesalahan.', 'error'));
            }
        });
    }
</script>
@endpush
@endsection
