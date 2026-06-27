{-- Tampilan antarmuka (UI) halaman form. --}
@extends('layouts.app')

@section('title', isset($leaveType) ? 'Edit Jenis Cuti' : 'Tambah Jenis Cuti')

@section('content')
<!-- Page Header -->
<section class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
    <div>
        <h2 class="text-headline-lg font-headline-lg text-on-background flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary" style="font-size: 32px;">
                {{ isset($leaveType) ? 'edit' : 'add_circle' }}
            </span>
            {{ isset($leaveType) ? 'Edit Jenis Cuti' : 'Tambah Jenis Cuti' }}
        </h2>
        <p class="text-body-md font-body-md text-secondary mt-xs">
            {{ isset($leaveType) ? 'Perbarui informasi jenis cuti' : 'Buat jenis cuti baru dalam sistem' }}
        </p>
    </div>
    <a href="{{ route('leave-types.index') }}" class="flex items-center gap-sm px-lg py-md bg-surface-container text-on-surface-variant rounded-xl font-label-md hover:bg-surface-container-high transition-all active:scale-95 no-underline">
        <span class="material-symbols-outlined">arrow_back</span>
        Kembali
    </a>
</section>

<!-- Form Container -->
<section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden max-w-4xl">
    <form action="{{ isset($leaveType) ? route('leave-types.update', $leaveType['id']) : route('leave-types.store') }}" method="POST" class="p-lg space-y-lg">
        @csrf
        @if(isset($leaveType))
            @method('PUT')
        @endif

        <!-- Kode & Max Hari -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            <div>
                <label class="text-label-md font-label-md text-on-surface mb-sm block">
                    Kode <span class="text-error">*</span>
                </label>
                <input type="text" name="kode" value="{{ old('kode', $leaveType['kode'] ?? '') }}" required maxlength="10"
                       class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('kode') border-error @enderror">
                <p class="text-secondary text-label-sm mt-xs">Contoh: CT, CS, CM, CTG</p>
                @error('kode')
                    <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="text-label-md font-label-md text-on-surface mb-sm block">
                    Max Hari per Pengajuan <span class="text-error">*</span>
                </label>
                <input type="number" name="max_hari_per_pengajuan" value="{{ old('max_hari_per_pengajuan', $leaveType['max_hari_per_pengajuan'] ?? 14) }}" required min="1" max="365"
                       class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('max_hari_per_pengajuan') border-error @enderror">
                @error('max_hari_per_pengajuan')
                    <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Nama -->
        <div>
            <label class="text-label-md font-label-md text-on-surface mb-sm block">
                Nama Jenis Cuti <span class="text-error">*</span>
            </label>
            <input type="text" name="nama" value="{{ old('nama', $leaveType['nama'] ?? '') }}" required maxlength="100"
                   class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('nama') border-error @enderror">
            @error('nama')
                <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
            @enderror
        </div>

        <!-- Deskripsi -->
        <div>
            <label class="text-label-md font-label-md text-on-surface mb-sm block">Deskripsi</label>
            <textarea name="deskripsi" rows="4"
                      class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('deskripsi') border-error @enderror">{{ old('deskripsi', $leaveType['deskripsi'] ?? '') }}</textarea>
            @error('deskripsi')
                <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
            @enderror
        </div>

        <!-- Butuh Dokumen -->
        <div class="flex items-center gap-sm p-md bg-surface-container-lowest rounded-xl border border-outline-variant">
            <input type="checkbox" name="butuh_dokumen" id="butuh_dokumen" value="1"
                   {{ old('butuh_dokumen', $leaveType['butuh_dokumen'] ?? false) ? 'checked' : '' }}
                   class="w-5 h-5 rounded border-outline-variant text-primary focus:ring-primary/20">
            <label for="butuh_dokumen" class="text-body-sm font-body-sm text-on-surface cursor-pointer">
                Membutuhkan Dokumen Pendukung
            </label>
        </div>
        <p class="text-secondary text-label-sm -mt-md ml-md">
            Aktifkan jika jenis cuti ini memerlukan dokumen pendukung (contoh: surat dokter)
        </p>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-sm pt-lg border-t border-outline-variant">
            <a href="{{ route('leave-types.index') }}" class="px-lg py-md bg-surface-container text-on-surface-variant rounded-xl font-label-md hover:bg-surface-container-high transition-all active:scale-95 no-underline">
                Batal
            </a>
            <button type="submit" class="px-lg py-md bg-primary text-on-primary rounded-xl font-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95">
                {{ isset($leaveType) ? 'Simpan Perubahan' : 'Tambah Jenis Cuti' }}
            </button>
        </div>
    </form>
</section>
@endsection
   