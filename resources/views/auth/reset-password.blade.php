@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<div class="auth-header">
    <h1 class="auth-title">Reset Password</h1>
    <p class="auth-subtitle">Masukkan password baru Anda</p>
</div>

<form action="{{ route('reset-password.post') }}" method="POST">
    @csrf
    <input type="hidden" name="token" value="{{ $token ?? '' }}">

    <div class="form-group">
        <label for="password" class="form-label">Password Baru</label>
        <div class="input-group">
            <i class="input-icon bi bi-lock"></i>
            <input type="password"
                   id="password"
                   name="password"
                   class="form-control"
                   placeholder="Minimal 8 karakter"
                   required>
            <div class="input-group-append">
                <button type="button"
                        class="btn-toggle-password"
                        onclick="togglePassword('password', this.querySelector('i'))">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        <small class="text-muted">Minimal 8 karakter, kombinasi huruf besar, kecil, dan angka</small>
        @error('password')
            <br><small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="form-group">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <div class="input-group">
            <i class="input-icon bi bi-lock-fill"></i>
            <input type="password"
                   id="password_confirmation"
                   name="password_confirmation"
                   class="form-control"
                   placeholder="Ulangi password baru"
                   required>
            <div class="input-group-append">
                <button type="button"
                        class="btn-toggle-password"
                        onclick="togglePassword('password_confirmation', this.querySelector('i'))">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-key me-2"></i>
        Reset Password
    </button>
</form>

<div class="auth-footer">
    <a href="{{ route('login') }}">Kembali ke Login</a>
</div>
@endsection
