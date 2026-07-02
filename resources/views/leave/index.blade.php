{{-- Tampilan antarmuka (UI) halaman index. --}}
@extends('layouts.app')

@section('title', 'Pengajuan Cuti Saya')

@section('content')
<div class="space-y-lg">
    <!-- Page Header -->
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-background">Pengajuan Cuti Saya</h2>
            <p class="text-body-md font-body-md text-secondary">Daftar pengajuan cuti yang telah Anda ajukan</p>
        </div>
        <a href="{{ route('leave.create') }}" class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95 no-underline">
            <span class="material-symbols-outlined">add_circle</span>
            Ajukan Cuti Baru
        </a>
    </section>

    <!-- Search & Filter -->
    @if(isset($searchQuery) && $searchQuery)
    <div class="flex items-center gap-md p-md bg-primary/5 border border-primary/20 rounded-xl">
        <span class="material-symbols-outlined text-primary">search</span>
        <span class="text-body-sm font-body-sm text-on-surface">
            Hasil pencarian: <strong>"{{ $searchQuery }}"</strong> — {{ count($leaveRequests) }} hasil ditemukan
        </span>
        <a href="{{ route('leave.index') }}" class="ml-auto text-label-sm font-label-sm text-primary hover:underline no-underline">Hapus pencarian</a>
    </div>
    @endif

    <!-- Table Container -->
    <section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-lg border-b border-outline-variant flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div class="flex items-center gap-md">
                <h3 class="text-label-md font-label-md text-on-background">Daftar Pengajuan</h3>
                <div class="px-2 py-1 bg-surface-container-highest text-secondary text-label-sm font-label-sm rounded-lg">
                    {{ count($leaveRequests) }} pengajuan
                </div>
            </div>
            <div class="flex items-center gap-sm">
                <form action="{{ route('leave.index') }}" method="GET" class="flex items-center gap-sm">
                    <select name="status" class="px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-body-sm focus:ring-2 focus:ring-primary/20 outline-none">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                        <option value="dibatalkan" {{ request('status') === 'dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
                    </select>
                    <button type="submit" class="flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded-lg text-label-sm font-label-sm hover:opacity-90 transition-all">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span>
                        Filter
                    </button>
                    <a href="{{ route('leave.index') }}" class="flex items-center gap-xs px-md py-sm border border-outline-variant rounded-lg text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low transition-all no-underline">
                        Reset
                    </a>
                </form>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-lowest text-secondary uppercase text-[11px] tracking-wider font-semibold">
                        <th class="px-lg py-md border-b border-outline-variant w-16">No</th>
                        <th class="px-lg py-md border-b border-outline-variant">Jenis Cuti</th>
                        <th class="px-lg py-md border-b border-outline-variant">Tanggal</th>
                        <th class="px-lg py-md border-b border-outline-variant">Hari</th>
                        <th class="px-lg py-md border-b border-outline-variant">Status</th>
                        <th class="px-lg py-md border-b border-outline-variant">Diajukan</th>
                        <th class="px-lg py-md border-b border-outline-variant text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($leaveRequests as $i => $leave)
                    <tr class="hover:bg-primary-container/5 transition-colors group">
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-lg py-md">
                            <div class="flex flex-col">
                                <span class="text-body-sm font-label-md text-on-background">{{ $leave['leave_type']['nama'] ?? '-' }}</span>
                                <span class="text-label-sm font-label-sm text-secondary">{{ $leave['leave_type']['kode'] ?? '' }}</span>
                            </div>
                        </td>
                        <td class="px-lg py-md">
                            <div class="flex flex-col">
                                <span class="text-body-sm font-body-sm text-on-surface">{{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M Y') }}</span>
                                <span class="text-label-sm font-label-sm text-secondary">s/d {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}</span>
                            </div>
                        </td>
                        <td class="px-lg py-md">
                            <span class="px-3 py-1 bg-primary/10 text-primary text-label-sm font-label-sm rounded-full">
                                {{ $leave['total_hari'] ?? 0 }} hari
                            </span>
                        </td>
                        <td class="px-lg py-md">
                            @php
                                $statusStyles = [
                                    'pending' => ['bg-orange-100', 'text-orange-600'],
                                    'disetujui' => ['bg-green-100', 'text-green-600'],
                                    'ditolak' => ['bg-error-container', 'text-error'],
                                    'dibatalkan' => ['bg-surface-container-highest', 'text-on-surface-variant'],
                                ];
                                $style = $statusStyles[$leave['status'] ?? ''] ?? ['bg-surface-container-highest', 'text-secondary'];
                            @endphp
                            <span class="px-3 py-1 {{ $style[0] }} {{ $style[1] }} text-label-sm font-label-sm rounded-full">
                                {{ ucfirst($leave['status'] ?? '-') }}
                            </span>
                        </td>
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">
                            {{ \Carbon\Carbon::parse($leave['created_at'])->format('d M Y') }}
                        </td>
                        <td class="px-lg py-md">
                            <div class="flex justify-center items-center gap-sm">
                                <a href="{{ route('leave.show', $leave['id']) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-primary-container/10 hover:text-primary transition-colors" title="Detail">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </a>
                                @if(($leave['status'] ?? '') === 'pending')
                                <a href="{{ route('leave.edit', $leave['id']) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-orange-500/10 hover:text-orange-600 transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </a>
                                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-error-container/20 hover:text-error transition-colors" onclick="cancelLeave('{{ $leave['id'] }}')" title="Batalkan">
                                    <span class="material-symbols-outlined text-[20px]">cancel</span>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-lg py-12 text-center">
                            <div class="flex flex-col items-center gap-md">
                                <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">inbox</span>
                                @if(isset($searchQuery) && $searchQuery)
                                    <p class="text-body-md font-body-md text-on-surface-variant">Tidak ada hasil untuk "<strong>{{ $searchQuery }}</strong>"</p>
                                    <a href="{{ route('leave.index') }}" class="text-label-sm font-label-sm text-primary hover:underline no-underline">Hapus pencarian</a>
                                @else
                                    <p class="text-body-md font-body-md text-on-surface-variant">Belum ada pengajuan cuti</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

@push('scripts')
<script>
function cancelLeave(id) {
    Swal.fire({
        title: 'Batalkan Pengajuan?',
        text: 'Pengajuan cuti yang dibatalkan tidak dapat dikembalikan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Ya, Batalkan',
        cancelButtonText: 'Tidak'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/leave/${id}/cancel`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Berhasil!', data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}
</script>
@endpush
@endsection
   