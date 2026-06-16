@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
<div class="auth-header">
    <h1 class="auth-title">Lupa Password</h1>
    <p class="auth-subtitle">Masukkan email Anda untuk menerima link reset password</p>
</div>

@if(session('success'))
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-2"></i> {{ session('success') }}
    </div>
@endif

<form action="{{ route('forgot-password.post') }}" method="POST">
    @csrf

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

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-send me-2"></i>
        Kirim Link Reset
    </button>
</form>

<div class="auth-footer">
    Ingat password? <a href="{{ route('login') }}">Login</a>
</div>
@endsection
