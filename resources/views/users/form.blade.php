@extends('layouts.app')

@section('title', isset($user) ? 'Edit Pengguna' : 'Tambah Pengguna')

@section('content')
<div class="space-y-lg">
    <!-- Page Header Section -->
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-background">{{ isset($user) ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h2>
            <p class="text-body-md font-body-md text-secondary">{{ isset($user) ? 'Perbarui data pengguna' : 'Tambah pengguna baru ke sistem' }}</p>
        </div>
    </section>

    <!-- Form Container -->
    <section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden max-w-4xl">
        <div class="p-lg">
            <form action="{{ isset($user) ? route('users.update', $user['id']) : route('users.store') }}" method="POST" class="space-y-lg">
                @csrf
                @if(isset($user))
                    @method('PUT')
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div>
                        <label for="full_name" class="block text-label-md font-label-md text-on-surface mb-sm">
                            Nama Lengkap <span class="text-error">*</span>
                        </label>
                        <input type="text" id="full_name" name="full_name"
                               value="{{ old('full_name', $user['full_name'] ?? '') }}"
                               class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('full_name') border-error @enderror"
                               required>
                        @error('full_name')
                            <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="block text-label-md font-label-md text-on-surface mb-sm">No. Telepon</label>
                        <input type="text" id="phone" name="phone"
                               value="{{ old('phone', $user['phone'] ?? '') }}"
                               maxlength="20"
                               class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('phone') border-error @enderror">
                        @error('phone')
                            <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if(!isset($user))
                <div>
                    <label for="email" class="block text-label-md font-label-md text-on-surface mb-sm">
                        Email <span class="text-error">*</span>
                    </label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('email') border-error @enderror"
                           required>
                    @error('email')
                        <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-label-md font-label-md text-on-surface mb-sm">
                        Password <span class="text-error">*</span>
                    </label>
                    <input type="password" id="password" name="password"
                           class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('password') border-error @enderror"
                           required minlength="8">
                    <p class="mt-xs text-label-sm font-label-sm text-secondary">Minimal 8 karakter</p>
                    @error('password')
                        <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
                    <div>
                        <label for="role" class="block text-label-md font-label-md text-on-surface mb-sm">
                            Role <span class="text-error">*</span>
                        </label>
                        <select id="role" name="role"
                                class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('role') border-error @enderror"
                                required>
                            <option value="">-- Pilih Role --</option>
                            <option value="karyawan" {{ old('role', $user['role'] ?? '') === 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                            <option value="manager" {{ old('role', $user['role'] ?? '') === 'manager' ? 'selected' : '' }}>Manager</option>
                            <option value="admin" {{ old('role', $user['role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role')
                            <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="department_id" class="block text-label-md font-label-md text-on-surface mb-sm">Departemen</label>
                        <select id="department_id" name="department_id"
                                class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('department_id') border-error @enderror">
                            <option value="">-- Pilih Departemen --</option>
                            @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept['id'] }}" {{ old('department_id', $user['department_id'] ?? '') === $dept['id'] ? 'selected' : '' }}>
                                    {{ $dept['nama'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')
                            <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="jatah_cuti_tahunan" class="block text-label-md font-label-md text-on-surface mb-sm">Jatah Cuti Tahunan (hari)</label>
                    <input type="number" id="jatah_cuti_tahunan" name="jatah_cuti_tahunan"
                           value="{{ old('jatah_cuti_tahunan', $user['jatah_cuti_tahunan'] ?? 12) }}"
                           min="0" max="365"
                           class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('jatah_cuti_tahunan') border-error @enderror">
                    @error('jatah_cuti_tahunan')
                        <p class="mt-xs text-label-sm font-label-sm text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-md pt-md border-t border-outline-variant">
                    <button type="submit" class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95">
                        <span class="material-symbols-outlined">check_circle</span>
                        {{ isset($user) ? 'Simpan Perubahan' : 'Tambah Pengguna' }}
                    </button>
                    <a href="{{ route('users.index') }}" class="flex items-center gap-sm px-lg py-md border border-outline-variant rounded-xl font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all no-underline">
                        <span class="material-symbols-outlined">cancel</span>
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection
 