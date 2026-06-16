@extends('layouts.app')

@section('title', isset($department) ? 'Edit Departemen' : 'Tambah Departemen')

@section('content')
<div class="page-header">
    <h1 class="page-title">{{ isset($department) ? 'Edit Departemen' : 'Tambah Departemen' }}</h1>
    <p class="page-subtitle">{{ isset($department) ? 'Perbarui data departemen' : 'Tambah departemen baru' }}</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ isset($department) ? route('departments.update', $department['id']) : route('departments.store') }}" method="POST">
                    @csrf
                    @if(isset($department))
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label for="kode" class="form-label">Kode Departemen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('kode') is-invalid @enderror" id="kode" name="kode"
                               value="{{ old('kode', $department['kode'] ?? '') }}" required maxlength="20">
                        @error('kode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="nama" class="form-label">Nama Departemen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('nama') is-invalid @enderror" id="nama" name="nama"
                               value="{{ old('nama', $department['nama'] ?? '') }}" required maxlength="100">
                        @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="manager_id" class="form-label">Manager</label>
                        <select class="form-select @error('manager_id') is-invalid @enderror" id="manager_id" name="manager_id">
                            <option value="">-- Pilih Manager --</option>
                            @foreach($managers ?? [] as $m)
                            <option value="{{ $m['id'] }}" {{ old('manager_id', $department['manager_id'] ?? '') === $m['id'] ? 'selected' : '' }}>
                                {{ $m['full_name'] }}
                            </option>
                            @endforeach
                        </select>
                        @error('manager_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="deskripsi" class="form-label">Deskripsi</label>
                        <textarea class="form-control @error('deskripsi') is-invalid @enderror" id="deskripsi" name="deskripsi" rows="3">{{ old('deskripsi', $department['deskripsi'] ?? '') }}</textarea>
                        @error('deskripsi') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-2"></i>{{ isset($department) ? 'Simpan Perubahan' : 'Tambah Departemen' }}
                        </button>
                        <a href="{{ route('departments.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
