{{-- Tampilan antarmuka (UI) halaman history. --}}
@extends('layouts.app')

@section('title', 'Riwayat Cuti')

@section('content')
<div class="space-y-lg">
    <!-- Page Header -->
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-background">Riwayat Cuti</h2>
            <p class="text-body-md font-body-md text-secondary">Riwayat semua pengajuan cuti yang telah diproses</p>
        </div>
        <a href="{{ route('leave.create') }}" class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:opacity-90 transition-all active:scale-95 no-underline">
            <span class="material-symbols-outlined">add_circle</span>
            Ajukan Cuti Baru
        </a>
    </section>

    <!-- Table Container -->
    <section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-lg border-b border-outline-variant flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div class="flex items-center gap-md">
                <h3 class="text-label-md font-label-md text-on-background">Riwayat Pengajuan</h3>
                <div class="px-2 py-1 bg-surface-container-highest text-secondary text-label-sm font-label-sm rounded-lg">
                    {{ count($leaves) }} riwayat
                </div>
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
                        <th class="px-lg py-md border-b border-outline-variant">Diproses</th>
                        <th class="px-lg py-md border-b border-outline-variant text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($leaves as $i => $leave)
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
                            @if(!empty($leave['tanggal_disetujui']))
                                {{ \Carbon\Carbon::parse($leave['tanggal_disetujui'])->format('d M Y') }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-lg py-md">
                            <div class="flex justify-center items-center">
                                <a href="{{ route('leave.show', $leave['id']) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-primary-container/10 hover:text-primary transition-colors" title="Detail">
                                    <span class="material-symbols-outlined text-[20px]">visibility</span>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-lg py-12 text-center">
                            <div class="flex flex-col items-center gap-md">
                                <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">archive</span>
                                <p class="text-body-md font-body-md text-on-surface-variant">Belum ada riwayat cuti</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
   