@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="page-header">
    <h1 class="page-title">Pengaturan</h1>
    <p class="page-subtitle">Kelola pengaturan keamanan dan preferensi akun Anda</p>
</div>

<div class="row">
    <div class="col-lg-6">
        <!-- 2FA Settings -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-shield-lock me-2"></i>Autentikasi Dua Faktor (2FA)
            </div>
            <div class="card-body">
                <p class="text-muted">Autentikasi dua faktor menambahkan lapisan keamanan ekstra dengan meminta kode verifikasi saat login.</p>

                <div class="d-flex justify-content-between align-items-center p-3 rounded" style="background: var(--light-bg);">
                    <div>
                        <strong>Status 2FA</strong>
                        <br>
                        @if(!empty($profile['two_factor_enabled']))
                            <span class="badge bg-success mt-1">Aktif</span>
                        @else
                            <span class="badge bg-secondary mt-1">Nonaktif</span>
                        @endif
                    </div>
                    <form action="{{ route('settings.2fa') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-{{ !empty($profile['two_factor_enabled']) ? 'outline-warning' : 'success' }}">
                            <i class="bi bi-{{ !empty($profile['two_factor_enabled']) ? 'shield-x' : 'shield-check' }} me-1"></i>
                            {{ !empty($profile['two_factor_enabled']) ? 'Nonaktifkan' : 'Aktifkan' }}
                        </button>
                    </form>
                </div>

                <div class="alert alert-info mt-3 mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>Jika 2FA diaktifkan, Anda akan diminta memasukkan kode 6 digit yang dikirim ke email setiap kali login.</small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <!-- Change Password -->
        <div class="card" id="password">
            <div class="card-header">
                <i class="bi bi-key me-2"></i>Ubah Password
            </div>
            <div class="card-body">
                @if($errors->has('current_password'))
                    <div class="alert alert-danger">{{ $errors->first('current_password') }}</div>
                @endif
                @if($errors->has('new_password'))
                    <div class="alert alert-danger">{{ $errors->first('new_password') }}</div>
                @endif

                <form action="{{ route('settings.password') }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <small class="text-muted">Minimal 8 karakter</small>
                    </div>

                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Ubah Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Account Info -->
<div class="card mt-3">
    <div class="card-header">
        <i class="bi bi-person-badge me-2"></i>Informasi Akun
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <div class="p-3 rounded text-center" style="background: var(--light-bg);">
                    <small class="text-muted d-block">Email</small>
                    <strong>{{ session('user_email') }}</strong>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded text-center" style="background: var(--light-bg);">
                    <small class="text-muted d-block">Role</small>
                    <strong>{{ ucfirst(session('user_role')) }}</strong>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded text-center" style="background: var(--light-bg);">
                    <small class="text-muted d-block">Jatah Cuti Tahunan</small>
                    <strong>{{ $profile['jatah_cuti_tahunan'] ?? 12 }} hari</strong>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3 rounded text-center" style="background: var(--light-bg);">
                    <small class="text-muted d-block">Sisa Cuti</small>
                    <strong>{{ $profile['sisa_cuti'] ?? 0 }} hari</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
