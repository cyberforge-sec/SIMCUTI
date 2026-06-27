@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<!-- Header -->
<div class="space-y-sm text-center">
    <h2 class="font-headline-lg text-headline-lg text-on-background">Selamat Datang</h2>
    <p class="font-body-md text-body-md text-on-surface-variant">Silakan masuk ke akun anda untuk melanjutkan.</p>
</div>

@if(session('success'))
    <div class="p-md bg-green-50 border border-green-200 rounded-xl text-green-700 text-body-sm">
        <span class="material-symbols-outlined text-green-600 align-middle mr-1" style="font-size: 18px;">check_circle</span>
        {{ session('success') }}
    </div>
@endif

<!-- OAuth Buttons -->
<div class="grid grid-cols-2 gap-md">
    <a href="{{ route('oauth.redirect', 'google') }}" class="flex items-center justify-center gap-sm py-md border border-outline-variant/50 bg-white/50 rounded-xl hover:bg-white hover:border-primary/30 transition-all active:scale-95 duration-200 shadow-sm">
        <img src="https://www.gstatic.com/images/branding/product/1x/gsa_512dp.png" alt="Google" class="w-5 h-5 object-contain">
        <span class="font-label-md text-label-md">Google</span>
    </a>
    <a href="{{ route('oauth.redirect', 'github') }}" class="flex items-center justify-center gap-sm py-md border border-outline-variant/50 bg-white/50 rounded-xl hover:bg-white hover:border-primary/30 transition-all active:scale-95 duration-200 shadow-sm">
        <img src="https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png" alt="GitHub" class="w-5 h-5 object-contain">
        <span class="font-label-md text-label-md">GitHub</span>
    </a>
</div>

<!-- Login Form -->
<form action="{{ route('login.post') }}" method="POST" class="space-y-lg mt-0" id="loginForm">
    @csrf

    <!-- Email -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="email">Alamat Email</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">mail</span>
            <input class="w-full pl-[48px] pr-md py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="email"
                   name="email"
                   value="{{ old('email') }}"
                   placeholder="adiva@simcuti.com"
                   type="email"
                   required
                   autofocus>
        </div>
        @error('email')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Password -->
    <div class="space-y-sm">
        <div class="flex justify-between items-center ml-1">
            <label class="font-label-md text-label-md text-on-surface-variant" for="password">Password</label>
            <a class="font-label-sm text-label-sm text-primary hover:underline" href="{{ route('forgot-password') }}">Lupa Password?</a>
        </div>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
            <input class="w-full pl-[48px] pr-[48px] py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="password"
                   name="password"
                   placeholder="••••••••"
                   type="password"
                   required>
            <button class="material-symbols-outlined absolute right-md top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors"
                    type="button"
                    onclick="togglePassword('password', this)">visibility</button>
        </div>
    </div>

    <!-- Captcha -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="captcha">Captcha</label>
        <div class="flex gap-md items-center">
            <div class="h-[60px] bg-white border border-outline-variant/60 rounded-xl overflow-hidden flex items-center justify-center">
                <img src="{{ $captchaImage }}" alt="Captcha" id="captchaImage" class="h-full w-full object-contain">
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

    <!-- Remember Me -->
    <div class="flex items-center gap-md">
        <input type="checkbox"
               id="remember"
               name="remember"
               class="w-5 h-5 border-2 border-outline-variant/60 rounded cursor-pointer hover:border-primary transition-colors">
        <label for="remember" class="font-body-sm text-body-sm text-on-surface-variant cursor-pointer">Ingat Saya</label>
    </div>

    <!-- Submit Button -->
    <button type="submit"
            id="btnSubmit"
            class="btn-gradient w-full py-md text-on-primary rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300">
        <span id="btnText">Masuk ke Akun</span>
        <span id="btnLoading" style="display: none;" class="flex items-center justify-center gap-sm">
            <span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">refresh</span>
            Memproses...
        </span>
    </button>
</form>

<!-- Footer Link -->
<div class="text-center">
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        Belum punya akun? <a class="text-primary font-semibold hover:underline" href="{{ route('register') }}">Daftar Sekarang</a>
    </p>
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
        btnLoading.style.display = 'flex';
    });
</script>
@endpush
  