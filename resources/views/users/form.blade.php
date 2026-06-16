@extends('layouts.app')

@section('title', isset($user) ? 'Edit Pengguna' : 'Tambah Pengguna')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ isset($user) ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h1>
    <p class="page-subtitle">{{ isset($user) ? 'Perbarui data pengguna' : 'Tambah pengguna baru ke sistem' }}</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ isset($user) ? route('users.update', $user['id']) : route('users.store') }}" method="POST">
                    @csrf
                    @if(isset($user))
                        @method('PUT')
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="full_name" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('full_name') is-invalid @enderror" id="full_name" name="full_name"
                                   value="{{ old('full_name', $user['full_name'] ?? '') }}" required>
                            @error('full_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label">No. Telepon</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone"
                                   value="{{ old('phone', $user['phone'] ?? '') }}" maxlength="20">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    @if(!isset($user))
                    <div class="mb-4">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                               value="{{ old('email') }}" required>
                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password"
                               required minlength="8">
                        @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="">-- Pilih Role --</option>
                                <option value="karyawan" {{ old('role', $user['role'] ?? '') === 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                                <option value="manager" {{ old('role', $user['role'] ?? '') === 'manager' ? 'selected' : '' }}>Manager</option>
                                <option value="admin" {{ old('role', $user['role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="department_id" class="form-label">Departemen</label>
                            <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                                <option value="">-- Pilih Departemen --</option>
                                @foreach($departments ?? [] as $dept)
                                <option value="{{ $dept['id'] }}" {{ old('department_id', $user['department_id'] ?? '') === $dept['id'] ? 'selected' : '' }}>
                                    {{ $dept['nama'] }}
                                </option>
                                @endforeach
                            </select>
                            @error('department_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="jatah_cuti_tahunan" class="form-label">Jatah Cuti Tahunan (hari)</label>
                        <input type="number" class="form-control @error('jatah_cuti_tahunan') is-invalid @enderror"
                               id="jatah_cuti_tahunan" name="jatah_cuti_tahunan"
                               value="{{ old('jatah_cuti_tahunan', $user['jatah_cuti_tahunan'] ?? 12) }}" min="0" max="365">
                        @error('jatah_cuti_tahunan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>{{ isset($user) ? 'Simpan Perubahan' : 'Tambah Pengguna' }}
                        </button>
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
