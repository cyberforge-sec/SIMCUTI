@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
<div class="space-y-lg">
    <!-- Page Header -->
    <section>
        <h2 class="text-headline-lg font-headline-lg text-on-background">Profil Saya</h2>
        <p class="text-body-md font-body-md text-secondary">Kelola informasi profil dan akun Anda</p>
    </section>

    <!-- Profile Hero Card -->
    <section class="bg-surface rounded-2xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="relative h-32 bg-gradient-to-r from-primary-container to-primary"></div>
        <div class="px-lg pb-lg -mt-12 flex flex-col sm:flex-row sm:items-end gap-lg">
            <div class="relative">
                <img src="{{ session('profile_photo_url') ?? 'https://ui-avatars.com/api/?name=' . urlencode(session('user_name')) . '&size=120&background=032bbe&color=fff&bold=true' }}"
                     alt="Profile" class="w-24 h-24 rounded-2xl border-4 border-surface object-cover bg-surface-dim shadow-lg">
                <label for="photoInput" class="absolute -bottom-2 -right-2 w-8 h-8 bg-primary text-on-primary rounded-full flex items-center justify-center cursor-pointer hover:bg-primary-container transition-all shadow-md">
                    <span class="material-symbols-outlined text-[18px]">photo_camera</span>
                </label>
                <form action="{{ route('profile.photo') }}" method="POST" enctype="multipart/form-data" id="photoForm" class="hidden">
                    @csrf
                    <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png" onchange="document.getElementById('photoForm').submit()">
                </form>
            </div>
            <div class="flex-1">
                <h3 class="text-headline-md font-headline-md text-on-background">{{ session('user_name') }}</h3>
                <p class="text-body-sm font-body-sm text-secondary">{{ session('user_email') }}</p>
                <span class="inline-block mt-xs px-3 py-1 bg-primary/10 text-primary text-label-sm font-label-sm rounded-full">
                    {{ ucfirst(session('user_role')) }}
                </span>
            </div>
            <div class="flex gap-sm">
                @if(session('profile_photo_url'))
                <form action="{{ route('profile.photo.delete') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex items-center gap-xs px-md py-sm border border-outline-variant rounded-xl text-label-sm font-label-sm text-error hover:bg-error-container/10 transition-all">
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                        Hapus Foto
                    </button>
                </form>
                @endif
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 gap-lg">
        <!-- Edit Profile Form -->
        <div>
            <section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="p-lg border-b border-outline-variant bg-surface-container-low/50">
                    <div class="flex items-center gap-md">
                        <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-[20px]">person</span>
                        </div>
                        <h4 class="text-label-md font-label-md text-on-background">Informasi Pribadi</h4>
                    </div>
                </div>
                <div class="p-lg">
                    <form action="{{ route('profile.update') }}" method="POST" class="space-y-lg">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                            <div>
                                <label for="full_name" class="block text-label-md font-label-md text-on-surface mb-sm">
                                    Nama Lengkap <span class="text-error">*</span>
                                </label>
                                <input type="text" id="full_name" name="full_name"
                                       value="{{ old('full_name', $profile['full_name'] ?? '') }}"
                                       class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('full_name') border-error @enderror"
                                       required>
                                @error('full_name')
                                    <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="phone" class="block text-label-md font-label-md text-on-surface mb-sm">No. Telepon</label>
                                <input type="text" id="phone" name="phone"
                                       value="{{ old('phone', $profile['phone'] ?? '') }}"
                                       maxlength="20" placeholder="08xxxxxxxxxx"
                                       class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('phone') border-error @enderror">
                                @error('phone')
                                    <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-label-md font-label-md text-on-surface mb-sm">Email</label>
                                <input type="email" value="{{ session('user_email') }}" disabled
                                       class="w-full px-md py-sm bg-surface-container-low border border-outline-variant rounded-xl text-body-sm text-secondary cursor-not-allowed">
                                <p class="mt-xs text-label-sm font-label-sm text-secondary">Email tidak dapat diubah</p>
                            </div>

                            <div>
                                <label class="block text-label-md font-label-md text-on-surface mb-sm">Departemen</label>
                                @if(session('user_role') === 'admin')
                                    <select name="department_id" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                                        <option value="">— Belum Ditentukan —</option>
                                        @foreach($departments ?? [] as $dept)
                                            <option value="{{ $dept['id'] }}" {{ ($profile['department_id'] ?? '') === $dept['id'] ? 'selected' : '' }}>
                                                {{ $dept['nama'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <select disabled class="w-full px-md py-sm bg-surface-container-low border border-outline-variant rounded-xl text-body-sm text-secondary cursor-not-allowed">
                                        <option>—</option>
                                        @foreach($departments ?? [] as $dept)
                                            @if(($profile['department_id'] ?? '') === $dept['id'])
                                                <option selected>{{ $dept['nama'] }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <p class="mt-xs text-label-sm font-label-sm text-secondary">Hubungi admin untuk mengubah</p>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                            <button type="submit" class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95">
                                <span class="material-symbols-outlined">check_circle</span>
                                Simpan Perubahan
                            </button>
                            <button type="button" onclick="deleteProfile()" class="flex items-center gap-sm px-md py-md border border-error/30 text-error rounded-xl font-label-md text-label-md hover:bg-error-container/10 transition-all">
                                <span class="material-symbols-outlined text-[18px]">delete_forever</span>
                                Hapus Akun
                            </button>
                        </div>
                    </form>
                </div>
            </section>
        </div>

        <!-- Account Info & Quick Links -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            <!-- Account Info Card -->
            <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="p-lg border-b border-outline-variant bg-surface-container-low/50">
                    <div class="flex items-center gap-md">
                        <div class="w-8 h-8 bg-secondary-container rounded-lg flex items-center justify-center text-on-secondary-container">
                            <span class="material-symbols-outlined text-[20px]">shield</span>
                        </div>
                        <h4 class="text-label-md font-label-md text-on-background">Informasi Akun</h4>
                    </div>
                </div>
                <div class="p-lg space-y-md">
                    <div class="flex justify-between items-center py-sm border-b border-outline-variant/50">
                        <span class="text-body-sm font-body-sm text-secondary">User ID</span>
                        <span class="text-label-sm font-label-sm text-on-background font-mono">{{ substr(session('user_id'), 0, 8) }}...</span>
                    </div>
                    <div class="flex justify-between items-center py-sm border-b border-outline-variant/50">
                        <span class="text-body-sm font-body-sm text-secondary">Bergabung</span>
                        <span class="text-label-sm font-label-sm text-on-background">
                            {{ isset($profile['created_at']) ? \Carbon\Carbon::parse($profile['created_at'])->format('d M Y') : '-' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-sm">
                        <span class="text-body-sm font-body-sm text-secondary">Login Terakhir</span>
                        <span class="text-label-sm font-label-sm text-on-background">
                            {{ isset($profile['last_login_at']) ? \Carbon\Carbon::parse($profile['last_login_at'])->diffForHumans() : '-' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Links Card -->
            <div class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
                <div class="p-lg border-b border-outline-variant bg-surface-container-low/50">
                    <div class="flex items-center gap-md">
                        <div class="w-8 h-8 bg-primary-fixed rounded-lg flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-[20px]">bolt</span>
                        </div>
                        <h4 class="text-label-md font-label-md text-on-background">Aksi Cepat</h4>
                    </div>
                </div>
                <div class="p-sm">
                    <a href="{{ route('settings') }}" class="flex items-center gap-md px-md py-md rounded-lg hover:bg-surface-container-low transition-colors no-underline group">
                        <div class="w-9 h-9 bg-secondary-container rounded-lg flex items-center justify-center text-on-secondary-container group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">settings</span>
                        </div>
                        <span class="text-body-sm font-label-md text-on-background">Pengaturan Akun</span>
                    </a>
                    <a href="{{ route('settings') }}#password" class="flex items-center gap-md px-md py-md rounded-lg hover:bg-surface-container-low transition-colors no-underline group">
                        <div class="w-9 h-9 bg-error-container rounded-lg flex items-center justify-center text-on-error-container group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">key</span>
                        </div>
                        <span class="text-body-sm font-label-md text-on-background">Ubah Password</span>
                    </a>
                    <a href="{{ route('leave.create') }}" class="flex items-center gap-md px-md py-md rounded-lg hover:bg-surface-container-low transition-colors no-underline group">
                        <div class="w-9 h-9 bg-primary/10 rounded-lg flex items-center justify-center text-primary group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-[20px]">add_circle</span>
                        </div>
                        <span class="text-body-sm font-label-md text-on-background">Ajukan Cuti</span>
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
