{{-- Tampilan antarmuka (UI) halaman edit. --}}
@extends('layouts.app')

@section('title', 'Edit Pengajuan Cuti')

@section('content')
<div class="page-header">
    <h1 class="page-title">Edit Pengajuan Cuti</h1>
    <p class="page-subtitle">Perbarui pengajuan cuti Anda yang masih pending</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined-square me-2">edit</span> Form Edit Pengajuan
            </div>
            <div class="card-body">
                <form action="{{ route('leave.update', $leave['id']) }}" method="POST" enctype="multipart/form-data" id="editLeaveForm">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="leave_type_id" class="form-label">Jenis Cuti</label>
                        <select class="form-select @error('leave_type_id') is-invalid @enderror" id="leave_type_id" name="leave_type_id" required>
                            @foreach($leaveTypes ?? [] as $type)
                            <option value="{{ $type['id'] }}" {{ ($leave['leave_type_id'] ?? '') === $type['id'] ? 'selected' : '' }}>
                                {{ $type['nama'] }} (Max: {{ $type['max_hari_per_pengajuan'] }} hari)
                            </option>
                            @endforeach
                        </select>
                        @error('leave_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="tanggal_mulai" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control @error('tanggal_mulai') is-invalid @enderror"
                                   id="tanggal_mulai" name="tanggal_mulai" value="{{ old('tanggal_mulai', $leave['tanggal_mulai'] ?? '') }}" required>
                            @error('tanggal_mulai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label for="tanggal_selesai" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control @error('tanggal_selesai') is-invalid @enderror"
                                   id="tanggal_selesai" name="tanggal_selesai" value="{{ old('tanggal_selesai', $leave['tanggal_selesai'] ?? '') }}" required>
                            @error('tanggal_selesai') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="alasan" class="form-label">Alasan Cuti</label>
                        <textarea class="form-control @error('alasan') is-invalid @enderror" id="alasan" name="alasan" rows="4">{{ old('alasan', $leave['alasan'] ?? '') }}</textarea>
                        @error('alasan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="lampiran" class="form-label">Lampiran Dokumen</label>
                        <input type="file" class="form-control" id="lampiran" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Format: PDF, JPG, PNG. Maksimal 5MB. Kosongkan jika tidak ingin mengubah.</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <span class="material-symbols-outlined me-2">check_circle</span>Simpan Perubahan
                        </button>
                        <a href="{{ route('leave.index') }}" class="btn btn-outline-secondary">
                            <span class="material-symbols-outlined me-2">cancel</span>Batal
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    document.getElementById('editLeaveForm').addEventListener('submit', function(e) {
        const alasan = document.getElementById('alasan').value.trim();
        if (alasan.length === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Alasan cuti wajib diisi.' });
            return false;
        }
        if (alasan.length < 20) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'Perhatian', text: 'Alasan pengajuan cuti minimal 20 karakter.' });
            return false;
        }
    });
</script>
@endpush
@endsection
   