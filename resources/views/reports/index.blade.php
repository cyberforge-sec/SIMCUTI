{-- Tampilan antarmuka (UI) halaman index. --}
@extends('layouts.app')

@section('title', 'Laporan Cuti')

@section('content')
<!-- Page Header -->
<section class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
    <div>
        <h2 class="text-headline-lg font-headline-lg text-on-background flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary" style="font-size: 32px;">analytics</span>
            Laporan Cuti
        </h2>
        <p class="text-body-md font-body-md text-secondary mt-xs">Generate dan export laporan pengajuan cuti</p>
    </div>
</section>

<!-- Stats Overview -->
<section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter mb-lg">
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center text-green-600">
            <span class="material-symbols-outlined" style="font-size: 28px; font-variation-settings: 'FILL' 1;">check_circle</span>
        </div>
        <div>
            <p class="text-body-sm font-body-sm text-secondary">Disetujui</p>
            <p class="text-headline-md font-headline-md text-on-background">{{ $stats['totalApproved'] ?? 0 }}</p>
        </div>
    </div>
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
        <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center text-orange-600">
            <span class="material-symbols-outlined" style="font-size: 28px; font-variation-settings: 'FILL' 1;">schedule</span>
        </div>
        <div>
            <p class="text-body-sm font-body-sm text-secondary">Pending</p>
            <p class="text-headline-md font-headline-md text-on-background">{{ $stats['totalPending'] ?? 0 }}</p>
        </div>
    </div>
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
        <div class="w-14 h-14 bg-error-container/20 rounded-full flex items-center justify-center text-error">
            <span class="material-symbols-outlined" style="font-size: 28px; font-variation-settings: 'FILL' 1;">cancel</span>
        </div>
        <div>
            <p class="text-body-sm font-body-sm text-secondary">Ditolak</p>
            <p class="text-headline-md font-headline-md text-on-background">{{ $stats['totalRejected'] ?? 0 }}</p>
        </div>
    </div>
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
        <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center text-primary">
            <span class="material-symbols-outlined" style="font-size: 28px; font-variation-settings: 'FILL' 1;">event_available</span>
        </div>
        <div>
            <p class="text-body-sm font-body-sm text-secondary">Hari Terpakai</p>
            <p class="text-headline-md font-headline-md text-on-background">{{ $stats['totalDays'] ?? 0 }}</p>
        </div>
    </div>
</section>

<!-- Filter Section -->
<section class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm mb-lg">
    <form action="{{ route('reports.index') }}" method="GET" id="filterForm" class="space-y-md">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
            <div>
                <label class="text-label-sm font-label-md text-secondary mb-xs block">Status</label>
                <select name="status" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                    <option value="">Semua Status</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="disetujui" {{ request('status') === 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') === 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div>
                <label class="text-label-sm font-label-md text-secondary mb-xs block">Jenis Cuti</label>
                <select name="leave_type_id" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                    <option value="">Semua Jenis</option>
                    @foreach($leaveTypes ?? [] as $lt)
                        <option value="{{ $lt['id'] }}" {{ request('leave_type_id') == $lt['id'] ? 'selected' : '' }}>{{ $lt['nama'] }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-label-sm font-label-md text-secondary mb-xs block">Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
            </div>
            <div>
                <label class="text-label-sm font-label-md text-secondary mb-xs block">Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
            </div>
        </div>

        @if(session('user_role') === 'admin')
        <div class="grid grid-cols-1 md:grid-cols-4 gap-md">
            <div>
                <label class="text-label-sm font-label-md text-secondary mb-xs block">Departemen</label>
                <select name="department_id" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                    <option value="">Semua Departemen</option>
                    @foreach($departments ?? [] as $dept)
                        <option value="{{ $dept['id'] }}" {{ request('department_id') == $dept['id'] ? 'selected' : '' }}>{{ $dept['nama'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @endif

        <div class="flex items-center gap-sm pt-md border-t border-outline-variant">
            <button type="submit" class="flex items-center gap-xs px-lg py-sm bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-all active:scale-95">
                <span class="material-symbols-outlined" style="font-size: 18px;">search</span>
                Tampilkan
            </button>
            <a href="{{ route('reports.index') }}" class="flex items-center gap-xs px-lg py-sm border border-outline-variant rounded-xl font-label-md text-on-surface-variant hover:bg-surface-container-low transition-all active:scale-95 no-underline">
                <span class="material-symbols-outlined" style="font-size: 18px;">refresh</span>
                Reset
            </a>
        </div>
    </form>
</section>

<!-- Export Buttons -->
<section class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm mb-lg">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h3 class="text-label-md font-label-md text-on-background flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary" style="font-size: 20px;">download</span>
                Export Laporan
            </h3>
            <p class="text-body-sm text-secondary mt-xs">Download laporan dalam format yang Anda inginkan</p>
        </div>
        <div class="flex items-center gap-sm">
            <a href="{{ route('reports.export', ['format' => 'csv'] + request()->query()) }}" class="flex items-center gap-xs px-md py-sm bg-green-100 text-green-700 rounded-xl font-label-sm hover:bg-green-200 transition-all active:scale-95 no-underline">
                <span class="material-symbols-outlined" style="font-size: 18px;">table_view</span>
                CSV
            </a>
            <a href="{{ route('reports.export', ['format' => 'excel'] + request()->query()) }}" class="flex items-center gap-xs px-md py-sm bg-primary/10 text-primary rounded-xl font-label-sm hover:bg-primary/20 transition-all active:scale-95 no-underline">
                <span class="material-symbols-outlined" style="font-size: 18px;">description</span>
                Excel
            </a>
            <a href="{{ route('reports.export', ['format' => 'pdf'] + request()->query()) }}" class="flex items-center gap-xs px-md py-sm bg-error-container/20 text-error rounded-xl font-label-sm hover:bg-error-container/30 transition-all active:scale-95 no-underline">
                <span class="material-symbols-outlined" style="font-size: 18px;">picture_as_pdf</span>
                PDF
            </a>
        </div>
    </div>
</section>

<!-- Table Container -->
<section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="p-lg border-b border-outline-variant flex items-center justify-between">
        <div class="flex items-center gap-md">
            <h3 class="text-label-md font-label-md text-on-background flex items-center gap-sm">
                <span class="material-symbols-outlined text-primary" style="font-size: 20px;">table_view</span>
                Data Laporan
            </h3>
            <span class="px-2 py-1 bg-primary/10 text-primary text-label-sm rounded-full">{{ count($reports) }} data</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-lowest text-secondary uppercase text-[11px] tracking-wider font-semibold">
                    <th class="px-lg py-md border-b border-outline-variant w-16">No</th>
                    <th class="px-lg py-md border-b border-outline-variant">Karyawan</th>
                    <th class="px-lg py-md border-b border-outline-variant">Departemen</th>
                    <th class="px-lg py-md border-b border-outline-variant">Jenis Cuti</th>
                    <th class="px-lg py-md border-b border-outline-variant">Tanggal Mulai</th>
                    <th class="px-lg py-md border-b border-outline-variant">Tanggal Selesai</th>
                    <th class="px-lg py-md border-b border-outline-variant">Hari</th>
                    <th class="px-lg py-md border-b border-outline-variant">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-outline-variant">
                @forelse($reports as $i => $r)
                    @php
                        $statusStyles = [
                            'pending' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600', 'icon' => 'schedule'],
                            'disetujui' => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'icon' => 'check_circle'],
                            'ditolak' => ['bg' => 'bg-error-container', 'text' => 'text-error', 'icon' => 'cancel'],
                            'dibatalkan' => ['bg' => 'bg-surface-container-highest', 'text' => 'text-secondary', 'icon' => 'block'],
                        ];
                        $status = $r['status'] ?? '';
                        $style = $statusStyles[$status] ?? $statusStyles['pending'];
                    @endphp
                    <tr class="hover:bg-primary-container/5 transition-colors group">
                        <td class="px-lg py-md text-body-sm text-secondary">{{ $i + 1 }}</td>
                        <td class="px-lg py-md">
                            <span class="text-body-sm font-label-md text-on-background">{{ e($r['user_name'] ?? '-') }}</span>
                        </td>
                        <td class="px-lg py-md text-body-sm text-on-surface-variant">{{ $r['department_name'] ?? '-' }}</td>
                        <td class="px-lg py-md text-body-sm text-on-surface-variant">{{ $r['leave_type_name'] ?? '-' }}</td>
                        <td class="px-lg py-md text-body-sm text-secondary">{{ $r['tanggal_mulai'] ?? '-' }}</td>
                        <td class="px-lg py-md text-body-sm text-secondary">{{ $r['tanggal_selesai'] ?? '-' }}</td>
                        <td class="px-lg py-md">
                            <span class="px-2 py-1 bg-primary/10 text-primary text-label-sm rounded-full">{{ $r['total_hari'] ?? 0 }}</span>
                        </td>
                        <td class="px-lg py-md">
                            <span class="inline-flex items-center gap-xs px-3 py-1 {{ $style['bg'] }} {{ $style['text'] }} text-label-sm font-label-sm rounded-full">
                                <span class="material-symbols-outlined" style="font-size: 14px;">{{ $style['icon'] }}</span>
                                {{ ucfirst($status ?: '-') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-lg py-12 text-center">
                            <div class="flex flex-col items-center gap-md">
                                <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">description</span>
                                <p class="text-body-md font-body-md text-on-surface-variant">Tidak Ada Data</p>
                                <p class="text-body-sm text-secondary">Belum ada laporan yang sesuai dengan filter yang dipilih</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
   