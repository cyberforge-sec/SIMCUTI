@extends('layouts.app')

@section('title', 'Jenis Cuti')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Jenis Cuti</h1>
        <p class="page-subtitle">Kelola jenis-jenis cuti yang tersedia</p>
    </div>
    <a href="{{ route('leave-types.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Tambah Jenis Cuti
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Nama Jenis Cuti</th>
                        <th>Max Hari/Pengajuan</th>
                        <th>Butuh Dokumen</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaveTypes as $i => $type)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><span class="badge bg-info">{{ $type['kode'] ?? '-' }}</span></td>
                        <td><strong>{{ $type['nama'] ?? '-' }}</strong></td>
                        <td>{{ $type['max_hari_per_pengajuan'] ?? 0 }} hari</td>
                        <td>
                            @if(!empty($type['butuh_dokumen']))
                                <span class="badge bg-warning text-dark">Ya</span>
                            @else
                                <span class="badge bg-secondary">Tidak</span>
                            @endif
                        </td>
                        <td>
                            @if(!empty($type['is_active']))
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('leave-types.edit', $type['id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteType('{{ $type['id'] }}', '{{ $type['nama'] }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-2 mb-0">Belum ada data jenis cuti</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function deleteType(id, nama) {
    Swal.fire({
        title: 'Hapus Jenis Cuti?',
        text: `Apakah Anda yakin ingin menghapus "${nama}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/leave-types/${id}`;
            form.innerHTML = `<input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="${csrfToken}">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
