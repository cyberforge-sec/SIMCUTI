@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-lg">
    <!-- Welcome Banner -->
    <div class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-primary to-primary-container p-lg">
            <div class="flex items-center justify-between">
                <div class="text-on-primary">
                    <h2 class="text-headline-lg font-headline-lg">Selamat Datang, {{ session('user_name') }}!</h2>
                    <p class="text-body-md font-body-md mt-sm opacity-90">Kelola cuti Anda dengan mudah dan pantau status pengajuan.</p>
                </div>
                <a href="{{ route('leave.create') }}" class="flex items-center gap-sm bg-surface text-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg hover:shadow-xl transition-all no-underline">
                    <span class="material-symbols-outlined">add</span>
                    Ajukan Cuti
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-lg">
        <!-- Sisa Cuti -->
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label-md font-label-md text-secondary">Sisa Cuti</p>
                    <p class="text-headline-lg font-headline-lg text-on-background mt-sm">{{ $leaveBalance->sisa ?? 0 }} <span class="text-body-md font-body-md text-secondary">hari</span></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-primary/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-primary" style="font-size: 24px;">event_available</span>
                </div>
            </div>
            <div class="mt-lg">
                <div class="flex justify-between text-label-sm font-label-sm text-secondary mb-xs">
                    <span>Terpakai {{ $leaveBalance->terpakai ?? 0 }} dari {{ $leaveBalance->total_jatah ?? 0 }} hari</span>
                </div>
                <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-primary to-primary-container transition-all duration-300" style="width: {{ $leaveBalance->total_jatah > 0 ? (($leaveBalance->total_jatah - $leaveBalance->sisa) / $leaveBalance->total_jatah * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        <!-- Menunggu Persetujuan -->
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label-md font-label-md text-secondary">Menunggu Persetujuan</p>
                    <p class="text-headline-lg font-headline-lg text-on-background mt-sm">{{ $pendingCount ?? 0 }} <span class="text-body-md font-body-md text-secondary">pengajuan</span></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-orange-500/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-orange-500" style="font-size: 24px;">pending</span>
                </div>
            </div>
            <a href="{{ route('leave.index') }}?status=pending" class="mt-lg inline-flex items-center gap-xs text-label-md font-label-md text-primary hover:underline no-underline">
                Lihat Semua
                <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
            </a>
        </div>

        <!-- Disetujui -->
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-label-md font-label-md text-secondary">Disetujui</p>
                    <p class="text-headline-lg font-headline-lg text-on-background mt-sm">{{ $approvedCount ?? 0 }} <span class="text-body-md font-body-md text-secondary">pengajuan</span></p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-green-500/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-green-600" style="font-size: 24px;">check_circle</span>
                </div>
            </div>
            <a href="{{ route('leave.index') }}?status=disetujui" class="mt-lg inline-flex items-center gap-xs text-label-md font-label-md text-primary hover:underline no-underline">
                Lihat Semua
                <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
            </a>
        </div>
    </div>

    <!-- Quick Actions & Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-lg">
        <!-- Quick Actions -->
        <div class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50">
                <h4 class="text-headline-md font-headline-md text-on-background">Aksi Cepat</h4>
            </div>
            <div class="p-lg">
                <div class="grid grid-cols-2 gap-md">
                    <a href="{{ route('leave.create') }}" class="flex flex-col items-center gap-sm p-lg bg-primary/5 hover:bg-primary/10 rounded-xl transition-all no-underline group">
                        <div class="w-14 h-14 rounded-xl bg-primary/10 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-primary" style="font-size: 28px;">add_circle</span>
                        </div>
                        <span class="text-label-md font-label-md text-on-background text-center">Ajukan Cuti</span>
                    </a>

                    <a href="{{ route('leave.index') }}" class="flex flex-col items-center gap-sm p-lg bg-secondary-container/30 hover:bg-secondary-container/50 rounded-xl transition-all no-underline group">
                        <div class="w-14 h-14 rounded-xl bg-secondary-container/50 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-on-secondary-container" style="font-size: 28px;">event_note</span>
                        </div>
                        <span class="text-label-md font-label-md text-on-background text-center">Pengajuan Saya</span>
                    </a>

                    <a href="{{ route('leave.history') }}" class="flex flex-col items-center gap-sm p-lg bg-surface-container-high/50 hover:bg-surface-container-high rounded-xl transition-all no-underline group">
                        <div class="w-14 h-14 rounded-xl bg-surface-container-high flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-on-surface-variant" style="font-size: 28px;">history</span>
                        </div>
                        <span class="text-label-md font-label-md text-on-background text-center">Riwayat Cuti</span>
                    </a>

                    <a href="{{ route('profile.edit') }}" class="flex flex-col items-center gap-sm p-lg bg-error-container/10 hover:bg-error-container/20 rounded-xl transition-all no-underline group">
                        <div class="w-14 h-14 rounded-xl bg-error-container/20 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <span class="material-symbols-outlined text-error" style="font-size: 28px;">person</span>
                        </div>
                        <span class="text-label-md font-label-md text-on-background text-center">Edit Profil</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Leave Requests -->
        <div class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50 flex items-center justify-between">
                <h4 class="text-headline-md font-headline-md text-on-background">Pengajuan Terbaru</h4>
                <a href="{{ route('leave.index') }}" class="text-label-md font-label-md text-primary hover:underline no-underline">Lihat Semua</a>
            </div>
            <div class="divide-y divide-outline-variant/30">
                @forelse($recentLeaves as $leave)
                <div class="px-lg py-md hover:bg-surface-container-low/50 transition-colors">
                    <div class="flex items-start justify-between gap-md">
                        <div class="flex-1">
                            <p class="text-body-md font-label-md text-on-background">{{ $leave['leave_type']['nama'] ?? 'Cuti' }}</p>
                            <p class="text-body-sm font-body-sm text-secondary mt-xs">
                                {{ \Carbon\Carbon::parse($leave['tanggal_mulai'])->format('d M Y') }} - {{ \Carbon\Carbon::parse($leave['tanggal_selesai'])->format('d M Y') }}
                            </p>
                            @if(!empty($leave['alasan']))
                                <p class="text-body-sm font-body-sm text-on-surface-variant mt-xs line-clamp-1">{{ e($leave['alasan']) }}</p>
                            @endif
                        </div>
                        <div class="flex flex-col items-end gap-xs">
                            @php
                                $statusStyles = [
                                    'pending' => ['bg-orange-500/10', 'text-orange-500', 'Menunggu'],
                                    'disetujui' => ['bg-green-500/10', 'text-green-600', 'Disetujui'],
                                    'ditolak' => ['bg-error/10', 'text-error', 'Ditolak'],
                                    'dibatalkan' => ['bg-on-surface-variant/10', 'text-on-surface-variant', 'Dibatalkan'],
                                ];
                                $status = $leave['status'] ?? 'pending';
                                $style = $statusStyles[$status] ?? $statusStyles['pending'];
                            @endphp
                            <span class="px-sm py-xs rounded-lg text-label-sm font-label-sm {{ $style[0] }} {{ $style[1] }}">
                                {{ $style[2] }}
                            </span>
                            <span class="text-label-sm font-label-sm text-secondary">{{ $leave['total_hari'] ?? 0 }} hari</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="px-lg py-12 text-center">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">inbox</span>
                    <p class="text-body-md font-body-md text-on-surface-variant mt-md">Belum ada pengajuan cuti</p>
                    <a href="{{ route('leave.create') }}" class="mt-md inline-flex items-center gap-sm text-label-md font-label-md text-primary hover:underline no-underline">
                        Ajukan Cuti Pertama
                        <span class="material-symbols-outlined" style="font-size: 16px;">arrow_forward</span>
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Calendar & Info -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
        <!-- Calendar -->
        <div class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden lg:col-span-1">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50">
                <h4 class="text-headline-md font-headline-md text-on-background">Kalender</h4>
            </div>
            <div class="p-lg">
                <div class="text-center mb-md">
                    <p class="text-label-md font-label-md text-on-background">{{ now()->format('F Y') }}</p>
                </div>
                <div class="grid grid-cols-7 gap-xs text-center">
                    <div class="text-label-sm font-label-sm text-secondary py-xs">Min</div>
                    <div class="text-label-sm font-label-sm text-secondary py-xs">Sen</div>
                    <div class="text-label-sm font-label-sm text-secondary py-xs">Sel</div>
                    <div class="text-label-sm font-label-sm text-secondary py-xs">Rab</div>
                    <div class="text-label-sm font-label-sm text-secondary py-xs">Kam</div>
                    <div class="text-label-sm font-label-sm text-secondary py-xs">Jum</div>
                    <div class="text-label-sm font-label-sm text-secondary py-xs">Sab</div>

                    @php
                        $daysInMonth = now()->daysInMonth;
                        $firstDayOfWeek = now()->startOfMonth()->dayOfWeek;
                        $today = now()->day;
                    @endphp

                    @for($i = 0; $i < $firstDayOfWeek; $i++)
                        <div></div>
                    @endfor

                    @for($day = 1; $day <= $daysInMonth; $day++)
                        <div class="aspect-square flex items-center justify-center rounded-lg text-body-sm font-body-sm {{ $day === $today ? 'bg-primary text-on-primary font-bold' : 'text-on-background' }}">
                            {{ $day }}
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        <!-- Tips & Panduan -->
        <div class="glass-card rounded-xl border border-outline-variant shadow-sm overflow-hidden lg:col-span-2">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50">
                <h4 class="text-headline-md font-headline-md text-on-background">Tips & Panduan</h4>
            </div>
            <div class="p-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
                    <div class="flex items-start gap-md p-md bg-primary/5 rounded-xl">
                        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-primary" style="font-size: 20px;">info</span>
                        </div>
                        <div>
                            <p class="text-label-md font-label-md text-on-background">Ajukan H-3</p>
                            <p class="text-body-sm font-body-sm text-secondary mt-xs">Ajukan cuti minimal 3 hari sebelum tanggal mulai</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-md p-md bg-green-500/5 rounded-xl">
                        <div class="w-10 h-10 rounded-lg bg-green-500/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-green-600" style="font-size: 20px;">check_circle</span>
                        </div>
                        <div>
                            <p class="text-label-md font-label-md text-on-background">Cek Saldo</p>
                            <p class="text-body-sm font-body-sm text-secondary mt-xs">Pastikan saldo cuti mencukupi sebelum mengajukan</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-md p-md bg-orange-500/5 rounded-xl">
                        <div class="w-10 h-10 rounded-lg bg-orange-500/10 flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-orange-500" style="font-size: 20px;">description</span>
                        </div>
                        <div>
                            <p class="text-label-md font-label-md text-on-background">Dokumen Pendukung</p>
                            <p class="text-body-sm font-body-sm text-secondary mt-xs">Upload dokumen untuk cuti sakit atau khusus</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
  