@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="space-y-lg">
    <!-- Page Header Section -->
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-background">Pengaturan</h2>
            <p class="text-body-md font-body-md text-secondary">Kelola pengaturan keamanan dan preferensi akun Anda</p>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
        <!-- 2FA Settings -->
        <section class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center gap-md mb-lg">
                <div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">shield</span>
                </div>
                <div>
                    <h3 class="text-label-md font-label-md text-on-background">Autentikasi Dua Faktor (2FA)</h3>
                    <p class="text-body-sm font-body-sm text-secondary">Lapisan keamanan ekstra untuk akun Anda</p>
                </div>
            </div>

            <div class="bg-surface-container-lowest rounded-xl p-md border border-outline-variant flex flex-col sm:flex-row sm:items-center justify-between gap-md">
                <div>
                    <p class="text-label-md font-label-md text-on-background">Status 2FA</p>
                    @if(!empty($profile['two_factor_enabled']))
                        <span class="inline-flex items-center gap-xs mt-xs text-label-sm font-label-sm text-green-600">
                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                            Aktif
                        </span>
                    @else
                        <span class="inline-flex items-center gap-xs mt-xs text-label-sm font-label-sm text-error">
                            <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                            Nonaktif
                        </span>
                    @endif
                </div>
                <form action="{{ route('settings.2fa') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-sm px-lg py-sm rounded-xl font-label-md text-label-md transition-all active:scale-95 {{ !empty($profile['two_factor_enabled']) ? 'border border-outline-variant text-on-surface-variant hover:bg-error-container/10 hover:text-error' : 'bg-primary text-on-primary shadow-lg shadow-primary/20 hover:opacity-90' }}">
                        <span class="material-symbols-outlined text-[20px]">
                            {{ !empty($profile['two_factor_enabled']) ? 'shield_lock' : 'verified_user' }}
                        </span>
                        {{ !empty($profile['two_factor_enabled']) ? 'Nonaktifkan' : 'Aktifkan' }}
                    </button>
                </form>
            </div>

            <div class="mt-md p-md bg-surface-container-low rounded-xl flex items-start gap-sm">
                <span class="material-symbols-outlined text-[20px] text-primary mt-[2px]">info</span>
                <p class="text-body-sm font-body-sm text-secondary">Jika 2FA diaktifkan, Anda akan diminta memasukkan kode 6 digit yang dikirim ke email setiap kali login.</p>
            </div>
        </section>

        <!-- Change Password -->
        <section class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-center gap-md mb-lg">
                <div class="w-12 h-12 bg-secondary-container rounded-full flex items-center justify-center text-on-secondary-container">
                    <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">key</span>
                </div>
                <div>
                    <h3 class="text-label-md font-label-md text-on-background">Ubah Password</h3>
                    <p class="text-body-sm font-body-sm text-secondary">Perbarui password akun Anda</p>
                </div>
            </div>

            @if($errors->has('current_password'))
                <div class="mb-md p-md bg-error-container/10 border border-error/20 rounded-xl flex items-center gap-sm">
                    <span class="material-symbols-outlined text-[20px] text-error">error</span>
                    <p class="text-body-sm font-body-sm text-error">{{ $errors->first('current_password') }}</p>
                </div>
            @endif
            @if($errors->has('new_password'))
                <div class="mb-md p-md bg-error-container/10 border border-error/20 rounded-xl flex items-center gap-sm">
                    <span class="material-symbols-outlined text-[20px] text-error">error</span>
                    <p class="text-body-sm font-body-sm text-error">{{ $errors->first('new_password') }}</p>
                </div>
            @endif

            <form action="{{ route('settings.password') }}" method="POST" class="space-y-md">
                @csrf
                @method('PUT')

                <div>
                    <label for="current_password" class="block text-label-md font-label-md text-on-surface mb-sm">Password Saat Ini</label>
                    <input type="password" id="current_password" name="current_password" required
                           class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                </div>

                <div>
                    <label for="new_password" class="block text-label-md font-label-md text-on-surface mb-sm">Password Baru</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8"
                           class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                    <p class="mt-xs text-label-sm font-label-sm text-secondary">Minimal 8 karakter, huruf besar, huruf kecil, angka, dan karakter spesial</p>
                </div>

                <div>
                    <label for="new_password_confirmation" class="block text-label-md font-label-md text-on-surface mb-sm">Konfirmasi Password Baru</label>
                    <input type="password" id="new_password_confirmation" name="new_password_confirmation" required
                           class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                </div>

                <button type="submit" class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95">
                    <span class="material-symbols-outlined">check_circle</span>
                    Ubah Password
                </button>
            </form>
        </section>
    </div>

    <!-- Account Info -->
    <section class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm">
        <div class="flex items-center gap-md mb-lg">
            <div class="w-12 h-12 bg-error-container/20 rounded-full flex items-center justify-center text-error">
                <span class="material-symbols-outlined text-[28px]" style="font-variation-settings: 'FILL' 1;">person</span>
            </div>
            <div>
                <h3 class="text-label-md font-label-md text-on-background">Informasi Akun</h3>
                <p class="text-body-sm font-body-sm text-secondary">Detail akun Anda</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-md">
            <div class="bg-surface-container-lowest rounded-xl p-md border border-outline-variant text-center">
                <span class="material-symbols-outlined text-primary text-[24px] mb-sm block">mail</span>
                <p class="text-label-sm font-label-sm text-secondary">Email</p>
                <p class="text-label-md font-label-md text-on-background mt-xs break-all">{{ session('user_email') }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md border border-outline-variant text-center">
                <span class="material-symbols-outlined text-primary text-[24px] mb-sm block">badge</span>
                <p class="text-label-sm font-label-sm text-secondary">Role</p>
                <p class="text-label-md font-label-md text-on-background mt-xs">{{ ucfirst(session('user_role')) }}</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md border border-outline-variant text-center">
                <span class="material-symbols-outlined text-primary text-[24px] mb-sm block">event_available</span>
                <p class="text-label-sm font-label-sm text-secondary">Jatah Cuti Tahunan</p>
                <p class="text-label-md font-label-md text-on-background mt-xs">{{ $profile['jatah_cuti_tahunan'] ?? 12 }} hari</p>
            </div>
            <div class="bg-surface-container-lowest rounded-xl p-md border border-outline-variant text-center">
                <span class="material-symbols-outlined text-primary text-[24px] mb-sm block">calendar_month</span>
                <p class="text-label-sm font-label-sm text-secondary">Sisa Cuti</p>
                <p class="text-label-md font-label-md text-on-background mt-xs">{{ $profile['sisa_cuti'] ?? 0 }} hari</p>
            </div>
        </div>
    </section>
</div>
@endsection
  