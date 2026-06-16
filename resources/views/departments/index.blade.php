@extends('layouts.app')

@section('title', 'Departemen')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Departemen</h1>
        <p class="page-subtitle">Kelola data departemen perusahaan</p>
    </div>
    <a href="{{ route('departments.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Tambah Departemen
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
                        <th>Nama Departemen</th>
                        <th>Manager</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $i => $dept)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><span class="badge bg-primary">{{ $dept['kode'] ?? '-' }}</span></td>
                        <td><strong>{{ $dept['nama'] ?? '-' }}</strong></td>
                        <td>{{ $dept['manager_name'] ?? '-' }}</td>
                        <td>{{ Str::limit($dept['deskripsi'] ?? '-', 50) }}</td>
                        <td>
                            @if(!empty($dept['is_active']))
                                <span class="badge bg-success">Aktif</span>
                            @else
                                <span class="badge bg-secondary">Nonaktif</span>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('departments.edit', $dept['id']) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteDept('{{ $dept['id'] }}', '{{ $dept['nama'] }}')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                            <p class="mt-2 mb-0">Belum ada data departemen</p>
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
function deleteDept(id, nama) {
    Swal.fire({
        title: 'Hapus Departemen?',
        text: `Apakah Anda yakin ingin menghapus departemen "${nama}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/departments/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                }
            }).then(r => r.json()).then(() => {
                Swal.fire('Berhasil!', 'Departemen berhasil dihapus.', 'success');
                setTimeout(() => location.reload(), 1000);
            }).catch(() => {
                // Redirect-based fallback
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/departments/${id}`;
                form.innerHTML = `<input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="${csrfToken}">`;
                document.body.appendChild(form);
                form.submit();
            });
        }
    });
}
</script>
@endpush
