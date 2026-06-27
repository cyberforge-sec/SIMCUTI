{-- Tampilan antarmuka (UI) halaman index. --}
@extends('layouts.app')

@section('title', 'Departemen')

@section('content')
<!-- Page Header -->
<section class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
    <div>
        <h2 class="text-headline-lg font-headline-lg text-on-background flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary" style="font-size: 32px;">domain</span>
            Departemen
        </h2>
        <p class="text-body-md font-body-md text-secondary mt-xs">Kelola departemen dalam organisasi</p>
    </div>
    <a href="{{ route('departments.create') }}" class="flex items-center gap-sm px-lg py-md bg-primary text-on-primary rounded-xl font-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95 no-underline">
        <span class="material-symbols-outlined">add</span>
        Tambah Departemen
    </a>
</section>

<!-- Stats Overview -->
<section class="grid grid-cols-1 md:grid-cols-3 gap-lg mb-lg">
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
        <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center text-primary">
            <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">domain</span>
        </div>
        <div>
            <p class="text-body-sm font-body-sm text-secondary">Total Departemen</p>
            <p class="text-headline-md font-headline-md text-on-background">{{ count($departments) }}</p>
        </div>
    </div>
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-lg">
            <div class="w-14 h-14 bg-secondary-container rounded-full flex items-center justify-center text-on-secondary-container">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">verified_user</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Aktif</p>
                <p class="text-headline-md font-headline-md text-on-background">{{ collect($departments)->where('is_active', true)->count() }}</p>
            </div>
        </div>
    </div>
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center justify-between">
        <div class="flex items-center gap-lg">
            <div class="w-14 h-14 bg-error-container/20 rounded-full flex items-center justify-center text-error">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">block</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Nonaktif</p>
                <p class="text-headline-md font-headline-md text-on-background">{{ collect($departments)->where('is_active', false)->count() }}</p>
            </div>
        </div>
    </div>
</section>

<!-- Table Container -->
<section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="p-lg border-b border-outline-variant flex items-center justify-between">
        <div class="flex items-center gap-md">
            <h3 class="text-label-md font-label-md text-on-background">Daftar Departemen</h3>
            <span class="px-2 py-1 bg-primary/10 text-primary text-label-sm rounded-full">{{ count($departments) }} data</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-lowest text-secondary uppercase text-[11px] tracking-wider font-semibold">
                    <th class="px-lg py-md border-b border-outline-variant w-16">No</th>
                    <th class="px-lg py-md border-b border-outline-variant">Kode</th>
                    <th class="px-lg py-md border-b border-outline-variant">Nama Departemen</th>
                    <th class="px-lg py-md border-b border-outline-variant">Manager</th>
                    <th class="px-lg py-md border-b border-outline-variant">Deskripsi</th>
                    <th class="px-lg py-md border-b border-outline-variant">Status</th>
                    <th class="px-lg py-md border-b border-outline-variant text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($departments as $i => $dept)
                <tr class="hover:bg-primary-container/5 transition-colors group">
                    <td class="px-lg py-md text-body-sm font-body-sm text-secondary">{{ $i + 1 }}</td>
                    <td class="px-lg py-md">
                        <span class="px-3 py-1 bg-primary/10 text-primary text-label-sm font-label-sm rounded-full">{{ $dept['kode'] ?? '-' }}</span>
                    </td>
                    <td class="px-lg py-md">
                        <span class="text-body-sm font-label-md text-on-background">{{ $dept['nama'] ?? '-' }}</span>
                    </td>
                    <td class="px-lg py-md text-body-sm font-body-sm text-on-surface-variant">{{ $dept['manager_name'] ?? '-' }}</td>
                    <td class="px-lg py-md text-body-sm font-body-sm text-on-surface-variant">{{ Str::limit($dept['deskripsi'] ?? '-', 50) }}</td>
                    <td class="px-lg py-md">
                        @if(!empty($dept['is_active']))
                            <span class="flex items-center gap-xs text-label-sm font-label-sm text-green-600">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Aktif
                            </span>
                        @else
                            <span class="flex items-center gap-xs text-label-sm font-label-sm text-error">
                                <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                Nonaktif
                            </span>
                        @endif
                    </td>
                    <td class="px-lg py-md">
                        <div class="flex justify-center items-center gap-sm">
                            <a href="{{ route('departments.edit', $dept['id']) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-primary-container/10 hover:text-primary transition-colors">
                                <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
                            </a>
                            <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-error-container/20 hover:text-error transition-colors" onclick="deleteDept('{{ $dept['id'] }}', '{{ $dept['nama'] }}')">
                                <span class="material-symbols-outlined" style="font-size: 20px;">delete</span>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-lg py-12 text-center">
                        <div class="flex flex-col items-center gap-md">
                            <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">inbox</span>
                            <p class="text-body-md font-body-md text-on-surface-variant">Belum ada data departemen</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
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
   