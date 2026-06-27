@extends('layouts.auth')

@section('title', 'Verifikasi 2FA')

@section('content')
<!-- Header -->
<div class="space-y-sm text-center">
    <h2 class="font-headline-lg text-headline-lg text-on-background">Verifikasi Dua Faktor</h2>
    <p class="font-body-md text-body-md text-on-surface-variant">Masukkan kode 6 digit yang dikirim ke email Anda</p>
</div>

@if(session('success'))
    <div class="p-md bg-green-50 border border-green-200 rounded-xl text-green-700 text-body-sm">
        <span class="material-symbols-outlined text-green-600 align-middle mr-1" style="font-size: 18px;">check_circle</span>
        {{ session('success') }}
    </div>
@endif

<!-- Form -->
<form action="{{ route('2fa.verify') }}" method="POST" class="space-y-lg mt-0" id="twoFactorForm">
    @csrf

    <!-- Code Input -->
    <div class="space-y-sm">
        <label class="font-label-md text-label-md text-on-surface-variant ml-1" for="code">Kode Verifikasi</label>
        <div class="relative group">
            <span class="material-symbols-outlined absolute left-md top-1/2 -translate-y-1/2 text-outline group-focus-within:text-primary transition-colors">shield</span>
            <input class="w-full pl-[48px] pr-md py-md bg-white border border-outline-variant/60 rounded-xl focus:ring-4 focus:ring-primary/10 focus:border-primary outline-none transition-all font-body-md text-center text-2xl tracking-widest"
                   id="code"
                   name="code"
                   placeholder="000000"
                   type="text"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   required
                   autofocus
                   autocomplete="off">
        </div>
        @error('code')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Submit Button -->
    <button type="submit"
            id="btnVerify"
            class="btn-gradient w-full py-md text-on-primary rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300">
        <span id="btnText">Verifikasi</span>
        <span id="btnLoading" style="display: none;" class="flex items-center justify-center gap-sm">
            <span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">refresh</span>
            Memproses...
        </span>
    </button>
</form>

<!-- Footer Actions -->
<div class="flex justify-between items-center mt-md">
    <form action="{{ route('2fa.resend') }}" method="POST" class="inline">
        @csrf
        <button type="submit" class="font-body-sm text-body-sm text-primary hover:underline">
            <span class="material-symbols-outlined align-middle mr-1" style="font-size: 16px;">refresh</span>
            Kirim Ulang Kode
        </button>
    </form>
    <a href="{{ route('logout') }}"
       class="font-body-sm text-body-sm text-on-surface-variant hover:text-error transition-colors"
       onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
        <span class="material-symbols-outlined align-middle mr-1" style="font-size: 16px;">logout</span>
        Logout
    </a>
    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('twoFactorForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnVerify');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'flex';
    });
</script>
@endpush
  