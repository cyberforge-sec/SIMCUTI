@extends('layouts.auth')

@section('title', 'Registrasi')

@section('content')
<!-- Header -->
<div class="space-y-sm text-center">
    <h2 class="font-headline-lg text-headline-lg text-on-background">Buat Akun Baru</h2>
    <p class="font-body-md text-body-md text-on-surface-variant">Daftar untuk menggunakan SIMCUTI</p>
</div>

<!-- Register Form -->
<form action="{{ route('register.post') }}" method="POST" class="space-y-md mt-0" id="registerForm">
    @csrf

    <!-- Full Name -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="full_name">Nama Lengkap</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">person</span>
            <input class="w-full pl-[48px] pr-md py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="full_name"
                   name="full_name"
                   value="{{ old('full_name') }}"
                   placeholder="Adiva Dwi Aprianto"
                   type="text"
                   required
                   autofocus>
        </div>
        @error('full_name')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Email -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="email">Email</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">mail</span>
            <input class="w-full pl-[48px] pr-md py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="adiva@simcuti.com"
                   type="email"
                   required>
        </div>
        @error('email')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Phone -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="phone">Nomor Telepon</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">phone</span>
            <input class="w-full pl-[48px] pr-md py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="phone"
                   name="phone"
                   value="{{ old('phone') }}"
                   placeholder="08123456789"
                   type="tel"
                   required>
        </div>
        @error('phone')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password">Password</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
            <input class="w-full pl-[48px] pr-[48px] py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="password"
                   name="password"
                   placeholder="Masukkan password"
                   type="password"
                   required>
            <button class="material-symbols-outlined absolute right-md top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors"
                    type="button"
                    onclick="togglePassword('password', this)">visibility</button>
        </div>
        <!-- Password Strength -->
        <div class="mt-1">
            <div class="h-1 bg-outline-variant/30 rounded-full overflow-hidden">
                <div id="strength-bar" class="h-full w-0 bg-outline-variant/30 rounded-full transition-all duration-300"></div>
            </div>
            <small id="strength-text" class="text-xs text-outline mt-1 block"></small>
        </div>
        <small class="text-xs text-on-surface-variant/60 block mt-1">
            Min. 8 karakter, kombinasi huruf, angka, dan simbol
        </small>
        @error('password')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Confirm Password -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password_confirmation">Konfirmasi Password</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
            <input class="w-full pl-[48px] pr-[48px] py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="password_confirmation"
                   name="password_confirmation"
                   placeholder="Ulangi password"
                   type="password"
                   required>
            <button class="material-symbols-outlined absolute right-md top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors"
                    type="button"
                    onclick="togglePassword('password_confirmation', this)">visibility</button>
        </div>
        @error('password_confirmation')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Captcha -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="captcha">Captcha</label>
        <div class="flex gap-md items-center">
            <div class="h-[60px] bg-white border border-outline-variant/60 rounded-xl overflow-hidden flex items-center justify-center">
                <img src="{{ $captchaImage ?? '/captcha' }}" alt="Captcha" id="captchaImage" class="h-full w-full object-contain">
            </div>
            <button type="button"
                    class="h-[60px] px-md bg-white border border-outline-variant/60 rounded-xl hover:border-primary/30 hover:bg-primary/5 transition-all active:scale-95 flex items-center justify-center"
                    onclick="refreshCaptcha()"
                    title="Refresh Captcha">
                <span class="material-symbols-outlined text-primary">refresh</span>
            </button>
        </div>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">verified_user</span>
            <input class="w-full pl-[48px] pr-md py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="captcha"
                   name="captcha"
                   placeholder="Masukkan kode captcha"
                   type="text"
                   required
                   autocomplete="off">
        </div>
        @error('captcha')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Terms -->
    <div class="flex items-center gap-md">
        <input type="checkbox"
               id="terms"
               name="terms"
               required
               class="w-5 h-5 border-2 border-outline-variant/60 rounded cursor-pointer hover:border-primary transition-colors">
        <label for="terms" class="font-body-sm text-body-sm text-on-surface-variant cursor-pointer">
            Saya setuju dengan <a href="#" class="text-primary font-semibold hover:underline">Syarat & Ketentuan</a>
        </label>
    </div>
    @error('terms')
        <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
    @enderror

    <!-- Submit Button -->
    <button type="submit"
            id="btnSubmit"
            class="btn-gradient w-full py-md text-on-primary rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300">
        <span id="btnText">Daftar Sekarang</span>
        <span id="btnLoading" style="display: none;" class="flex items-center justify-center gap-sm">
            <span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">refresh</span>
            Memproses...
        </span>
    </button>
</form>

<!-- Footer Link -->
<div class="text-center">
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        Sudah punya akun? <a class="text-primary font-semibold hover:underline" href="{{ route('login') }}">Login Sekarang</a>
    </p>
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
            { width: '0%', color: '#c5c5d7', text: '' },
            { width: '20%', color: '#ef4444', text: 'Sangat Lemah' },
            { width: '40%', color: '#f97316', text: 'Lemah' },
            { width: '60%', color: '#eab308', text: 'Sedang' },
            { width: '80%', color: '#22c55e', text: 'Kuat' },
            { width: '100%', color: '#16a34a', text: 'Sangat Kuat' },
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
        btnLoading.style.display = 'flex';
    });
</script>
@endpush
  