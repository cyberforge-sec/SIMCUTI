{-- Tampilan antarmuka (UI) halaman forgot-password. --}
@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
<!-- Header -->
<div class="space-y-sm text-center">
    <h2 class="font-headline-lg text-headline-lg text-on-background">Lupa Password</h2>
    <p class="font-body-md text-body-md text-on-surface-variant">Masukkan email Anda untuk menerima link reset password</p>
</div>

@if(session('success'))
    <div class="p-md bg-green-50 border border-green-200 rounded-xl text-green-700 text-body-sm">
        <span class="material-symbols-outlined text-green-600 align-middle mr-1" style="font-size: 18px;">check_circle</span>
        {{ session('success') }}
    </div>
@endif

<!-- Form -->
<form action="{{ route('forgot-password.post') }}" method="POST" class="space-y-lg mt-0">
    @csrf

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
                   required
                   autofocus>
        </div>
        @error('email')
            <div class="text-error text-label-sm ml-1 mt-1">{{ $message }}</div>
        @enderror
    </div>

    <!-- Submit Button -->
    <button type="submit"
            class="btn-gradient w-full py-md text-on-primary rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300">
        Kirim Link Reset
    </button>
</form>

<!-- Footer Link -->
<div class="text-center">
    <p class="font-body-sm text-body-sm text-on-surface-variant">
        Ingat password? <a class="text-primary font-semibold hover:underline" href="{{ route('login') }}">Login</a>
    </p>
</div>
@endsection
   