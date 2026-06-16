@extends('layouts.auth')

@section('title', 'Registrasi')

@section('content')
<div class="auth-header">
    <h1 class="auth-title">Buat Akun Baru</h1>
    <p class="auth-subtitle">Daftar untuk menggunakan SIMCUTI</p>
</div>

<form action="{{ route('register.post') }}" method="POST" id="registerForm">
    @csrf

    <div class="form-row">
        <div class="form-group">
            <label for="full_name" class="form-label">Nama Lengkap</label>
            <div class="input-group">
                <i class="input-icon bi bi-person"></i>
                <input type="text"
                       id="full_name"
                       name="full_name"
                       class="form-control"
                       placeholder="Adiva Dwi Aprianto"
                       value="{{ old('full_name') }}"
                       required
                       autofocus>
            </div>
            @error('full_name')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>

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
                       required>
            </div>
            @error('email')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
    </div>

    <div class="form-row">
        <div class="form-group">
            <label for="phone" class="form-label">Nomor Telepon</label>
            <div class="input-group">
                <i class="input-icon bi bi-phone"></i>
                <input type="tel"
                       id="phone"
                       name="phone"
                       class="form-control"
                       placeholder="08123456789"
                       value="{{ old('phone') }}"
                       required>
            </div>
            @error('phone')
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
            <small class="text-muted" style="font-size: 0.75rem;">
                Min. 8 karakter, kombinasi huruf, angka, dan simbol
            </small>
            <div style="margin-top: 0.25rem;">
                <div style="height: 4px; background: #E5E7EB; border-radius: 2px; overflow: hidden;">
                    <div id="strength-bar" style="height: 100%; width: 0%; background: #E5E7EB; border-radius: 2px; transition: width 0.3s ease, background 0.3s ease;"></div>
                </div>
                <small id="strength-text" style="font-size: 0.7rem; color: #9CA3AF;"></small>
            </div>
            @error('password')
                <small class="text-danger">{{ $message }}</small>
            @enderror
        </div>
    </div>

    <div class="form-group">
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <div class="input-group">
            <i class="input-icon bi bi-lock-fill"></i>
            <input type="password"
                   id="password_confirmation"
                   name="password_confirmation"
                   class="form-control"
                   placeholder="Ulangi password"
                   required>
            <div class="input-group-append">
                <button type="button"
                        class="btn-toggle-password"
                        onclick="togglePassword('password_confirmation', this.querySelector('i'))">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        @error('password_confirmation')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <div class="form-group">
        <label for="captcha" class="form-label">Captcha</label>
        <div class="captcha-row">
            <div class="captcha-left">
                <div class="captcha-container">
                    <div class="captcha-image">
                        <img src="{{ $captchaImage ?? '/captcha' }}" alt="Captcha" id="captchaImage">
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

    <div class="form-check mb-2" style="font-size: 0.8125rem;">
        <input type="checkbox" class="form-check-input" id="terms" name="terms" required style="margin-right: 0.5rem;">
        <label class="form-check-label" for="terms">
            Saya setuju dengan <a href="#" class="text-decoration-none">Syarat & Ketentuan</a>
        </label>
        @error('terms')
            <br><small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <button type="submit" class="auth-btn-primary" id="btnSubmit">
        <i class="bi bi-person-plus" style="margin-right: 0.5rem;"></i>
        <span id="btnText">Daftar Sekarang</span>
        <span id="btnLoading" style="display: none;">
            <i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite; margin-right: 0.5rem;"></i>
            Memproses...
        </span>
    </button>
</form>

<div class="auth-footer">
    Sudah punya akun? <a href="{{ route('login') }}">Login Sekarang</a>
</div>
@endsection

@push('scripts')
<script>
    const passwordInput = document.getElementById('password');
    const strengthBar = document.getElementById('strength-bar');
    const strengthText = document.getElementById('strength-text');

    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;

        if (password.length >= 8) strength++;
        if (password.match(/[a-z]/)) strength++;
        if (password.match(/[A-Z]/)) strength++;
        if (password.match(/[0-9]/)) strength++;
        if (password.match(/[^a-zA-Z0-9]/)) strength++;

        const levels = [
            { width: '0%', color: '#E5E7EB', text: '' },
            { width: '20%', color: '#EF4444', text: 'Sangat Lemah' },
            { width: '40%', color: '#F97316', text: 'Lemah' },
            { width: '60%', color: '#EAB308', text: 'Sedang' },
            { width: '80%', color: '#22C55E', text: 'Kuat' },
            { width: '100%', color: '#16A34A', text: 'Sangat Kuat' },
        ];

        const level = password.length === 0 ? levels[0] : levels[strength];
        strengthBar.style.width = level.width;
        strengthBar.style.background = level.color;
        strengthText.textContent = level.text;
        strengthText.style.color = level.color;
    });

    document.getElementById('registerForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnSubmit');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.style.cursor = 'not-allowed';
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
    });
</script>
@endpush
