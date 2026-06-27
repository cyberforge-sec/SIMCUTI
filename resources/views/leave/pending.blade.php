@extends('layouts.app')

@section('title', 'Perlu Persetujuan')

@section('content')
<div class="space-y-lg">
    <!-- Page Header -->
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-background">Perlu Persetujuan</h2>
            <p class="text-body-md font-body-md text-secondary">Daftar pengajuan cuti yang menunggu keputusan Anda</p>
        </div>
    </section>

    <!-- Stats -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-lg">
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">pending_actions</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Menunggu</p>
                <p class="text-headline-md font-headline-md text-on-background">{{ count($pendingLeaves ?? []) }}</p>
            </div>
        </div>
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-secondary-container rounded-full flex items-center justify-center text-on-secondary-container">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">event</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Total Hari Cuti</p>
                <p class="text-headline-md font-headline-md text-on-background">
                    {{ collect($pendingLeaves ?? [])->sum('total_hari') }}
                </p>
            </div>
        </div>
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-error-container/20 rounded-full flex items-center justify-center text-error">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">priority_high</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Urgent</p>
                <p class="text-headline-md font-headline-md text-on-background">
                    {{ collect($pendingLeaves ?? [])->where('is_urgent', true)->count() }}
                </p>
            </div>
        </div>
    </section>

    <!-- Table Container -->
    <section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-lg border-b border-outline-variant flex flex-col md:flex-row md:items-center justify-between gap-md bg-surface-container-low/50">
            <div class="flex items-center gap-md">
                <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[20px]">assignment</span>
                </div>
                <h3 class="text-label-md font-label-md text-on-background">Daftar Pengajuan</h3>
                <div class="px-2 py-1 bg-surface-container-highest text-secondary text-label-sm font-label-sm rounded-lg">
                    {{ count($pendingLeaves ?? []) }} pengajuan
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-lowest text-secondary uppercase text-[11px] tracking-wider font-semibold">
                        <th class="px-lg py-md border-b border-outline-variant w-16">No</th>
                        <th class="px-lg py-md border-b border-outline-variant">Karyawan</th>
                        <th class="px-lg py-md border-b border-outline-variant">Jenis Cuti</th>
                        <th class="px-lg py-md border-b border-outline-variant">Tanggal</th>
                        <th class="px-lg py-md border-b border-outline-variant">Hari</th>
                        <th class="px-lg py-md border-b border-outline-variant">Diajukan</th>
                        <th class="px-lg py-md border-b border-outline-variant text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($pendingLeaves as $i => $leave)
                    <tr class="hover:bg-primary-container/5 transition-colors group" id="row-{{ $leave['id'] }}">
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-lg py-md">
                            <div class="flex items-center gap-md">
                                @php
                                    $initials = collect(explode(' ', $leave['user']['full_name'] ?? 'U'))
                                        ->take(2)
                                        ->map(fn($n) => strtoupper(substr($n, 0, 1)))
                                        ->join('');
                                    $avatarColors = [
                                        'bg-primary-fixed text-primary',
                                        'bg-secondary-fixed text-on-secondary-fixed-variant',
                                        'bg-surface-container-highest text-secondary',
                                    ];
                                    $colorIndex = $i % 3;
                                @endphp
                                <div class="w-8 h-8 rounded-full {{ $avatarColors[$colorIndex] }} flex items-center justify-center font-bold text-xs">
                                    {{ $initials }}
                                </div>
                                <span class="text-body-sm font-label-md text-on-background">{{ e($leave['user']['full_name'] ?? '-') }}</span>
                            </div>
                        </td>
                        <td class="px-lg py-md">
                            <div class="flex flex-col">
                                <span class="px-2 py-1 bg-secondary-container text-on-secondary-container text-label-sm font-label-sm rounded-lg inline-block w-fit">
                                    {{ $leave['leave_type']['kode'] ?? '-' }}
                                </span>
                                <span class="text-body-sm font-body-sm text-secondary mt-xs">{{ $leave['leave_type']['nama'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-lg py-md text-body-sm font-body-sm text-on-surface-variant">
                            {{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M') }} - {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}
                        </td>
                        <td class="px-lg py-md">
                            <span class="px-3 py-1 bg-primary/10 text-primary text-label-sm font-label-sm rounded-full">
                                {{ $leave['total_hari'] ?? 0 }} hari
                            </span>
                        </td>
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">
                            {{ \Carbon\Carbon::parse($leave['created_at'])->diffForHumans() }}
                        </td>
                        <td class="px-lg py-md">
                            <div class="flex justify-center items-center gap-sm">
                                <a href="{{ route('leave.show', $leave['id']) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-primary-container/10 hover:text-primary transition-colors" title="Detail">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </a>
                                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-green-500/10 hover:text-green-600 transition-colors" onclick="approveLeave('{{ $leave['id'] }}')" title="Setujui">
                                    <span class="material-symbols-outlined text-[20px]">check_circle</span>
                                </button>
                                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-error-container/20 hover:text-error transition-colors" onclick="showRejectModal('{{ $leave['id'] }}')" title="Tolak">
                                    <span class="material-symbols-outlined text-[20px]">cancel</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-lg py-12 text-center">
                            <div class="flex flex-col items-center gap-md">
                                <span class="material-symbols-outlined text-6xl text-green-500/30">check_circle</span>
                                <p class="text-body-md font-body-md text-on-surface-variant">Tidak ada pengajuan yang menunggu persetujuan</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Reject Modal -->
<div x-data="{ show: false, rejectId: '', rejectReason: '' }"
     x-on:show-reject-modal.window="show = true; rejectId = $event.detail.id; rejectReason = ''"
     x-show="show" x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center"
     style="display: none;">
    <div class="fixed inset-0 bg-black/50" @click="show = false"></div>
    <div x-show="show" x-transition
         class="relative bg-surface rounded-2xl shadow-2xl w-full max-w-md mx-4 z-10 border border-outline-variant">
        <div class="flex items-center justify-between px-lg py-md border-b border-outline-variant">
            <h5 class="font-label-md text-label-md text-on-surface flex items-center gap-sm">
                <span class="material-symbols-outlined text-error">cancel</span>Tolak Pengajuan
            </h5>
            <button type="button" class="text-secondary hover:text-on-surface bg-transparent border-none text-xl cursor-pointer" @click="show = false">&times;</button>
        </div>
        <div class="p-lg">
            <label for="rejectReason" class="block text-label-md font-label-md text-on-surface mb-sm">
                Alasan Penolakan <span class="text-error">*</span>
            </label>
            <textarea x-model="rejectReason" id="rejectReason" rows="3" minlength="10" required
                      placeholder="Jelaskan alasan penolakan (minimal 10 karakter)..."
                      class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm"></textarea>
        </div>
        <div class="flex justify-end gap-sm px-lg py-md border-t border-outline-variant">
            <button type="button" class="px-lg py-md border border-outline-variant rounded-xl font-label-md text-label-md text-on-surface-variant hover:bg-surface-container-low transition-all" @click="show = false">Batal</button>
            <button type="button" class="px-lg py-md bg-error text-on-error rounded-xl font-label-md text-label-md hover:opacity-90 transition-all" @click="
                if (rejectReason.length < 10) {
                    Swal.fire('Peringatan', 'Alasan penolakan minimal 10 karakter.', 'warning');
                    return;
                }
                fetch(`/leave/${rejectId}/reject`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ alasan: rejectReason })
                })
                .then(r => r.json())
                .then(data => {
                    show = false;
                    if (data.success) {
                        document.getElementById('row-' + rejectId).style.opacity = '0.5';
                        Swal.fire('Berhasil!', data.message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                });
            ">Tolak Pengajuan</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function approveLeave(id) {
    Swal.fire({
        title: 'Setujui Pengajuan?',
        text: 'Pastikan Anda telah memeriksa pengajuan dengan teliti.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        confirmButtonText: 'Ya, Setujui',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/leave/${id}/approve`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('row-' + id).style.opacity = '0.5';
                    Swal.fire('Berhasil!', data.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('Gagal!', data.message, 'error');
                }
            });
        }
    });
}

function showRejectModal(id) {
    window.dispatchEvent(new CustomEvent('show-reject-modal', { detail: { id } }));
}
</script>
@endpush
@endsection
  