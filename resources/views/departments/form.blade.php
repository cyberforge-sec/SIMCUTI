@extends('layouts.app')

@section('title', isset($department) ? 'Edit Departemen' : 'Tambah Departemen')

@section('content')
<!-- Page Header -->
<section class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
    <div>
        <h2 class="text-headline-lg font-headline-lg text-on-background flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary" style="font-size: 32px;">
                {{ isset($department) ? 'edit' : 'add_circle' }}
            </span>
            {{ isset($department) ? 'Edit Departemen' : 'Tambah Departemen' }}
        </h2>
        <p class="text-body-md font-body-md text-secondary mt-xs">
            {{ isset($department) ? 'Perbarui informasi departemen' : 'Buat departemen baru dalam organisasi' }}
        </p>
    </div>
    <a href="{{ route('departments.index') }}" class="flex items-center gap-sm px-lg py-md bg-surface-container text-on-surface-variant rounded-xl font-label-md hover:bg-surface-container-high transition-all active:scale-95 no-underline">
        <span class="material-symbols-outlined">arrow_back</span>
        Kembali
    </a>
</section>

<!-- Form Container -->
<section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden max-w-4xl">
    <form action="{{ isset($department) ? route('departments.update', $department['id']) : route('departments.store') }}" method="POST" class="p-lg space-y-lg">
        @csrf
        @if(isset($department))
            @method('PUT')
        @endif

        <!-- Kode & Nama -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-lg">
            <div>
                <label class="text-label-md font-label-md text-on-surface mb-sm block">
                    Kode Departemen <span class="text-error">*</span>
                </label>
                <input type="text" name="kode" value="{{ old('kode', $department['kode'] ?? '') }}" required maxlength="20"
                       class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('kode') border-error @enderror">
                @error('kode')
                    <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="text-label-md font-label-md text-on-surface mb-sm block">
                    Nama Departemen <span class="text-error">*</span>
                </label>
                <input type="text" name="nama" value="{{ old('nama', $department['nama'] ?? '') }}" required maxlength="100"
                       class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('nama') border-error @enderror">
                @error('nama')
                    <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Manager -->
        <div>
            <label class="text-label-md font-label-md text-on-surface mb-sm block">Manager</label>
            <select name="manager_id" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('manager_id') border-error @enderror">
                <option value="">-- Pilih Manager --</option>
                @foreach($managers ?? [] as $m)
                    <option value="{{ $m['id'] }}" {{ old('manager_id', $department['manager_id'] ?? '') == $m['id'] ? 'selected' : '' }}>
                        {{ $m['full_name'] }}
                    </option>
                @endforeach
            </select>
            @error('manager_id')
                <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
            @enderror
        </div>

        <!-- Deskripsi -->
        <div>
            <label class="text-label-md font-label-md text-on-surface mb-sm block">Deskripsi</label>
            <textarea name="deskripsi" rows="4"
                      class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm @error('deskripsi') border-error @enderror">{{ old('deskripsi', $department['deskripsi'] ?? '') }}</textarea>
            @error('deskripsi')
                <p class="text-error text-label-sm mt-xs">{{ $message }}</p>
            @enderror
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-sm pt-lg border-t border-outline-variant">
            <a href="{{ route('departments.index') }}" class="px-lg py-md bg-surface-container text-on-surface-variant rounded-xl font-label-md hover:bg-surface-container-high transition-all active:scale-95 no-underline">
                Batal
            </a>
            <button type="submit" class="px-lg py-md bg-primary text-on-primary rounded-xl font-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95">
                {{ isset($department) ? 'Simpan Perubahan' : 'Tambah Departemen' }}
            </button>
        </div>
    </form>
</section>
@endsection
 