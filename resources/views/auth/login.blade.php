@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<div class="auth-header">
    <h1 class="auth-title">Selamat Datang</h1>
    <p class="auth-subtitle">Masuk ke akun SIMCUTI Anda</p>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<form action="{{ route('login.post') }}" method="POST" id="loginForm">
    @csrf

    <div class="form-row">
        <a href="{{ route('oauth.redirect', 'github') }}" class="btn-oauth btn-oauth-github">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/></svg>
            GitHub
        </a>

        <a href="{{ route('oauth.redirect', 'google') }}" class="btn-oauth btn-oauth-google">
            <svg width="16" height="16" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Google
        </a>
    </div>

    <div class="divider"><span>atau login dengan email</span></div>

    <div class="form-group">
        <label for="email" class="form-label">Email</label>
        <div class="input-group">
            <i class="input-icon bi bi-envelope"></i>
            <input type="email"
                   id="email"
                   name="email"
                   class="form-control"
                   placeholder="nama@perusahaan.com"
                   value="{{ old('email') }}"
                   required
                   autofocus>
        </div>
        @error('email')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="input-group">
            <i class="input-icon bi bi-lock"></i>
            <input type="password"
                   id="password"
                   name="password"
                   class="form-control"
                   placeholder="Masukkan password"
                   required>
            <div class="input-group-append">
                <button type="button"
                        class="btn-toggle-password"
                        onclick="togglePassword('password', this.querySelector('i'))">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
    </div>

    <div class="form-group">
        <label for="captcha" class="form-label">Captcha</label>
        <div class="captcha-row">
            <div class="captcha-left">
                <div class="captcha-container">
                    <div class="captcha-image">
                        <img src="{{ $captchaImage }}" alt="Captcha" id="captchaImage">
                    </div>
                    <button type="button" class="btn-refresh-captcha" onclick="refreshCaptcha()" title="Refresh Captcha">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
            </div>
            <div class="captcha-right">
                <input type="text"
                       id="captcha"
                       name="captcha"
                       class="form-control"
                       style="padding-left: 1rem;"
                       placeholder="Masukkan kode captcha"
                       required
                       autocomplete="off">
            </div>
        </div>
        @error('captcha')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2" style="font-size: 0.8125rem;">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label" for="remember">Ingat Saya</label>
        </div>
        <a href="{{ route('forgot-password') }}" class="text-decoration-none">Lupa Password?</a>
    </div>

    <button type="submit" class="auth-btn-primary" id="btnSubmit">
        <span id="btnText">
            <i class="bi bi-box-arrow-in-right" style="margin-right: 0.5rem;"></i>
            Masuk
        </span>
        <span id="btnLoading" style="display: none;">
            <i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite; margin-right: 0.5rem;"></i>
            Memproses...
        </span>
    </button>
</form>

<div class="auth-footer">
    Belum punya akun? <a href="{{ route('register') }}">Daftar Sekarang</a>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmit');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
    });

    function refreshCaptcha() {
        fetch('/captcha/refresh', {
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('captchaImage').src = data.captcha;
            }
        });
    }
</script>
@endpush
