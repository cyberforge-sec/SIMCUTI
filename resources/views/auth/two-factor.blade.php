@extends('layouts.auth')

@section('title', 'Verifikasi 2FA')

@push('styles')
<style>
    .form-group {
        margin-bottom: 1.25rem;
    }
    .form-group .form-label {
        font-size: 0.9375rem;
        margin-bottom: 0.5rem;
    }
    .form-group .form-control {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
        font-size: 1rem;
        border-radius: 0.5rem;
    }
    .auth-header {
        margin-bottom: 1.5rem;
    }
    .auth-title {
        font-size: 1.5rem;
    }
    .auth-subtitle {
        font-size: 1rem;
    }
    .btn-verify {
        width: 100%;
        padding: 0.625rem;
        background: var(--color-primary, #4F46E5);
        color: white;
        border: none;
        border-radius: 0.75rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-verify:hover {
        background: var(--color-primary-dark, #4338CA);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
    }
    .btn-verify:disabled {
        transform: none;
        box-shadow: none;
        opacity: 0.7;
        cursor: not-allowed;
    }
    .resend-btn {
        color: var(--color-primary, #4F46E5);
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.9375rem;
        padding: 0;
    }
    .resend-btn:hover {
        text-decoration: underline;
    }
</style>
@endpush

@section('content')
<div class="auth-header">
    <h1 class="auth-title">Verifikasi Dua Faktor</h1>
    <p class="auth-subtitle">Masukkan kode 6 digit yang dikirim ke email Anda</p>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<form action="{{ route('2fa.verify') }}" method="POST" id="twoFactorForm">
    @csrf

    <div class="form-group">
        <label for="code" class="form-label">Kode Verifikasi</label>
        <div class="input-group">
            <i class="input-icon bi bi-shield-lock"></i>
            <input type="text"
                   id="code"
                   name="code"
                   class="form-control"
                   placeholder="000000"
                   maxlength="6"
                   pattern="[0-9]{6}"
                   style="font-size: 1.5rem; text-align: center; letter-spacing: 0.5rem;"
                   required
                   autofocus
                   autocomplete="off">
        </div>
        @error('code')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>

    <button type="submit" class="btn-verify" id="btnVerify">
        <span id="btnText">
            <i class="bi bi-check-circle" style="margin-right: 0.5rem;"></i>
            Verifikasi
        </span>
        <span id="btnLoading" style="display: none;">
            <i class="bi bi-arrow-repeat" style="animation: spin 1s linear infinite; margin-right: 0.5rem;"></i>
            Memproses...
        </span>
    </button>
</form>

<div class="d-flex justify-content-between align-items-center mt-3">
    <form action="{{ route('2fa.resend') }}" method="POST">
        @csrf
        <button type="submit" class="resend-btn">
            <i class="bi bi-arrow-clockwise" style="margin-right: 0.25rem;"></i> Kirim Ulang Kode
        </button>
    </form>
    <a href="{{ route('logout') }}" class="text-decoration-none text-muted" style="font-size: 0.9375rem;"
       onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
        <i class="bi bi-box-arrow-right" style="margin-right: 0.25rem;"></i> Logout
    </a>
    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>
</div>

@push('scripts')
<script>
    document.getElementById('twoFactorForm').addEventListener('submit', function() {
        const btn = document.getElementById('btnVerify');
        const btnText = document.getElementById('btnText');
        const btnLoading = document.getElementById('btnLoading');
        btn.disabled = true;
        btnText.style.display = 'none';
        btnLoading.style.display = 'inline';
    });
</script>
@endpush
@endsection
