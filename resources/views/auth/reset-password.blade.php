@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
<!-- Header -->
<div class="space-y-sm text-center">
    <h2 class="font-headline-lg text-headline-lg text-on-background">Reset Password</h2>
    <p class="font-body-md text-body-md text-on-surface-variant">Masukkan password baru Anda</p>
</div>

@if(session('success'))
    <div class="p-md bg-green-50 border border-green-200 rounded-xl text-green-700 text-body-sm">
        <span class="material-symbols-outlined text-green-600 align-middle mr-1" style="font-size: 18px;">check_circle</span>
        {{ session('success') }}
    </div>
@endif

<!-- Form -->
<form action="{{ route('reset-password.post') }}" method="POST" class="space-y-lg mt-0">
    @csrf
    <input type="hidden" name="token" value="{{ $token ?? '' }}">
    <input type="hidden" name="is_access_token" value="{{ !empty($isAccessToken) ? 1 : 0 }}">

    <!-- New Password -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="password">Password Baru</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">lock</span>
            <input class="w-full pl-[48px] pr-[48px] py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md"
                   id="password"
                   name="password"
                   placeholder="Minimal 8 karakter"
                   type="password"
                   required>
            <button class="material-symbols-outlined absolute right-md top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors"
                    type="button"
                    onclick="togglePassword('password', this)">visibility</button>
        </div>
        <small class="text-xs text-on-surface-variant/60 block mt-1 ml-1">Minimal 8 karakter, kombinasi huruf besar, kecil, dan angka</small>
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
                   placeholder="Ulangi password baru"
                   type="password"
                   required>
            <button class="material-symbols-outlined absolute right-md top-1/2 -translate-y-1/2 text-outline hover:text-on-surface transition-colors"
                    type="button"
                    onclick="togglePassword('password_confirmation', this)">visibility</button>
        </div>
    </div>

    <!-- Submit Button -->
    <button type="submit"
            class="btn-gradient w-full py-md text-on-primary rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300">
        Reset Password
    </button>
</form>

<!-- Footer Link -->
<div class="text-center">
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        <a class="text-primary font-semibold hover:underline" href="{{ route('login') }}">Kembali ke Login</a>
    </p>
</div>
@endsection
