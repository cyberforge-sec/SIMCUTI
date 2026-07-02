{{-- Tampilan antarmuka (UI) halaman team. --}}
@extends('layouts.app')

@section('title', 'Anggota Tim')

@section('content')
<div class="space-y-lg">
    <!-- Page Header -->
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-background">Anggota Tim</h2>
            <p class="text-body-md font-body-md text-secondary">Departemen: <span class="font-semibold text-on-background">{{ $departmentName }}</span> · {{ count($teamMembers) }} anggota</p>
        </div>
        <div class="flex items-center gap-sm">
            <div class="relative">
                <span class="absolute inset-y-0 left-3 flex items-center text-outline pointer-events-none">
                    <span class="material-symbols-outlined text-[20px]">search</span>
                </span>
                <input type="text" id="searchInput"
                       class="pl-10 pr-4 py-2 bg-surface-container-low border-none rounded-xl focus:ring-2 focus:ring-primary/20 w-64 text-body-sm font-body-sm"
                       placeholder="Cari anggota...">
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="grid grid-cols-1 sm:grid-cols-3 gap-lg">
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">group</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Total Anggota</p>
                <p class="text-headline-md font-headline-md text-on-background">{{ count($teamMembers) }}</p>
            </div>
        </div>
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-secondary-container rounded-full flex items-center justify-center text-on-secondary-container">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">verified_user</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Aktif</p>
                <p class="text-headline-md font-headline-md text-on-background">
                    {{ collect($teamMembers)->where('is_on_leave', false)->count() }}
                </p>
            </div>
        </div>
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-error-container/20 rounded-full flex items-center justify-center text-error">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">event_busy</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Sedang Cuti</p>
                <p class="text-headline-md font-headline-md text-on-background">
                    {{ collect($teamMembers)->where('is_on_leave', true)->count() }}
                </p>
            </div>
        </div>
    </section>

    <!-- Table Container -->
    <section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="p-lg border-b border-outline-variant flex items-center gap-md bg-surface-container-low/50">
            <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[20px]">groups</span>
            </div>
            <h3 class="text-label-md font-label-md text-on-background">Daftar Anggota</h3>
            <div class="px-2 py-1 bg-surface-container-highest text-secondary text-label-sm font-label-sm rounded-lg">
                {{ count($teamMembers) }} anggota
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="teamTable">
                <thead>
                    <tr class="bg-surface-container-lowest text-secondary uppercase text-[11px] tracking-wider font-semibold">
                        <th class="px-lg py-md border-b border-outline-variant w-16">No</th>
                        <th class="px-lg py-md border-b border-outline-variant">Anggota</th>
                        <th class="px-lg py-md border-b border-outline-variant">Email</th>
                        <th class="px-lg py-md border-b border-outline-variant">Role</th>
                        <th class="px-lg py-md border-b border-outline-variant">Sisa Cuti</th>
                        <th class="px-lg py-md border-b border-outline-variant">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($teamMembers as $i => $member)
                    <tr class="hover:bg-primary-container/5 transition-colors group">
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-lg py-md">
                            <div class="flex items-center gap-md">
                                @php
                                    $initials = collect(explode(' ', $member['full_name'] ?? 'U'))
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
                                <span class="text-body-sm font-label-md text-on-background">{{ $member['full_name'] ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">{{ $member['email'] ?? '-' }}</td>
                        <td class="px-lg py-md">
                            @php
                                $roleStyles = [
                                    'manager' => 'bg-secondary-container text-on-secondary-container',
                                    'karyawan' => 'bg-surface-container-highest text-secondary'
                                ];
                            @endphp
                            <span class="px-3 py-1 {{ $roleStyles[$member['role'] ?? ''] ?? 'bg-surface-container-highest text-secondary' }} text-label-sm font-label-sm rounded-full">
                                {{ ucfirst($member['role'] ?? '-') }}
                            </span>
                        </td>
                        <td class="px-lg py-md">
                            @php $sisa = $member['sisa_cuti'] ?? 0; @endphp
                            <span class="px-3 py-1 {{ $sisa > 3 ? 'bg-green-500/10 text-green-600' : 'bg-orange-500/10 text-orange-600' }} text-label-sm font-label-sm rounded-full">
                                {{ $sisa }} hari
                            </span>
                        </td>
                        <td class="px-lg py-md">
                            @if($member['is_on_leave'] ?? false)
                                <span class="flex items-center gap-xs text-label-sm font-label-sm text-error">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                    Sedang Cuti
                                </span>
                            @else
                                <span class="flex items-center gap-xs text-label-sm font-label-sm text-green-600">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Aktif
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-lg py-12 text-center">
                            <div class="flex flex-col items-center gap-md">
                                <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">groups</span>
                                <p class="text-body-md font-body-md text-on-surface-variant">Belum ada anggota tim di departemen ini</p>
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
// Logika fitur pencarian
const searchInput = document.getElementById('searchInput');
const tableRows = document.querySelectorAll('#teamTable tbody tr');

if (searchInput && tableRows.length) {
    searchInput.addEventListener('input', () => {
        const query = searchInput.value.toLowerCase();
        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}
</script>
@endpush
@endsection
   