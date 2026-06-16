@extends('layouts.app')

@section('title', isset($leaveType) ? 'Edit Jenis Cuti' : 'Tambah Jenis Cuti')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ isset($leaveType) ? 'Edit Jenis Cuti' : 'Tambah Jenis Cuti' }}</h1>
    <p class="page-subtitle">{{ isset($leaveType) ? 'Perbarui data jenis cuti' : 'Tambah jenis cuti baru' }}</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ isset($leaveType) ? route('leave-types.update', $leaveType['id']) : route('leave-types.store') }}" method="POST">
                    @csrf
                    @if(isset($leaveType))
                        @method('PUT')
                    @endif

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="kode" class="form-label">Kode <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('kode') is-invalid @enderror" id="kode" name="kode"
                                   value="{{ old('kode', $leaveType['kode'] ?? '') }}" required maxlength="10">
                            @error('kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Contoh: CT, CS, CM, CTG</small>
                        </div>
                        <div class="col-md-6">
                            <label for="max_hari_per_pengajuan" class="form-label">Max Hari per Pengajuan <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('max_hari_per_pengajuan') is-invalid @enderror"
                                   id="max_hari_per_pengajuan" name="max_hari_per_pengajuan"
                                   value="{{ old('max_hari_per_pengajuan', $leaveType['max_hari_per_pengajuan'] ?? 14) }}" min="1" max="365" required>
                            @error('max_hari_per_pengajuan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="nama" class="form-label">Nama Jenis Cuti <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama"
                               value="{{ old('nama', $leaveType['nama'] ?? '') }}" required maxlength="100">
                        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $leaveType['deskripsi'] ?? '') }}</textarea>
                        @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="butuh_dokumen" name="butuh_dokumen" value="1"
                                   {{ old('butuh_dokumen', $leaveType['butuh_dokumen'] ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="butuh_dokumen">Membutuhkan Dokumen Pendukung</label>
                        </div>
                        <small class="text-muted">Aktifkan jika jenis cuti ini memerlukan dokumen pendukung (contoh: surat dokter)</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>{{ isset($leaveType) ? 'Simpan Perubahan' : 'Tambah Jenis Cuti' }}
                        </button>
                        <a href="{{ route('leave-types.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
