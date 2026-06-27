{-- Tampilan antarmuka (UI) halaman admin. --}
<!-- Page Title Section -->
<div class="mb-lg">
    <h2 class="text-headline-lg font-headline-lg text-on-background">Ringkasan Dashboard</h2>
    <p class="text-body-md font-body-md text-secondary">Pantau data dan laporan terbaru.</p>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-gutter">
    <!-- Total Pengguna -->
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-primary/5 rounded-full -mr-8 -mt-8 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="flex justify-between items-start relative z-10">
            <div>
                <p class="text-label-sm font-label-md text-secondary uppercase tracking-wider">TOTAL PENGGUNA</p>
                <h3 class="text-headline-lg font-headline-lg text-on-background mt-sm">{{ $totalUsers ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-primary-fixed flex items-center justify-center text-primary shrink-0">
                <span class="material-symbols-outlined">group</span>
            </div>
        </div>
        <div class="mt-lg flex items-center gap-xs text-label-sm font-label-sm">
            <span class="text-primary font-bold">Stabil</span>
            <span class="text-secondary">sejak bulan lalu</span>
        </div>
    </div>

    <!-- Departemen -->
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-secondary-container/10 rounded-full -mr-8 -mt-8 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="flex justify-between items-start relative z-10">
            <div>
                <p class="text-label-sm font-label-md text-secondary uppercase tracking-wider">DEPARTEMEN</p>
                <h3 class="text-headline-lg font-headline-lg text-on-background mt-sm">{{ $totalDepartments ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-secondary-fixed flex items-center justify-center text-secondary shrink-0">
                <span class="material-symbols-outlined">domain</span>
            </div>
        </div>
        <div class="mt-lg flex items-center gap-xs text-label-sm font-label-sm text-secondary">
            <span class="font-bold">Unit aktif</span>
        </div>
    </div>

    <!-- Cuti Disetujui -->
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-green-500/5 rounded-full -mr-8 -mt-8 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="flex justify-between items-start relative z-10">
            <div>
                <p class="text-label-sm font-label-md text-secondary uppercase tracking-wider">CUTI DISETUJUI</p>
                <h3 class="text-headline-lg font-headline-lg text-on-background mt-sm">{{ $approvedCount ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center text-green-600 shrink-0">
                <span class="material-symbols-outlined">check_circle</span>
            </div>
        </div>
        <div class="mt-lg flex items-center gap-xs text-label-sm font-label-sm text-green-600">
            <span class="font-bold">Telah diproses</span>
        </div>
    </div>

    <!-- Cuti Menunggu -->
    <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
        <div class="absolute top-0 right-0 w-24 h-24 bg-error-container/10 rounded-full -mr-8 -mt-8 group-hover:scale-110 transition-transform duration-500"></div>
        <div class="flex justify-between items-start relative z-10">
            <div>
                <p class="text-label-sm font-label-md text-secondary uppercase tracking-wider">CUTI MENUNGGU</p>
                <h3 class="text-headline-lg font-headline-lg text-on-background mt-sm">{{ $pendingCount ?? 0 }}</h3>
            </div>
            <div class="w-12 h-12 rounded-xl bg-error-container flex items-center justify-center text-error shrink-0">
                <span class="material-symbols-outlined">pending_actions</span>
            </div>
        </div>
        <div class="mt-lg flex items-center gap-xs text-label-sm font-label-sm text-on-surface-variant">
            <span class="font-bold">{{ ($pendingCount ?? 0) > 0 ? 'Perlu tindakan' : 'Tidak ada tindakan' }}</span>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="grid grid-cols-1 gap-gutter">
    <div class="space-y-gutter">
        <section class="glass-card rounded-2xl border border-outline-variant shadow-sm overflow-hidden transition-all">
            <div class="px-lg py-md border-b border-outline-variant bg-surface-container-low/50">
                <h4 class="text-headline-md font-headline-md text-on-background">Aktivitas Terbaru</h4>
            </div>
            <div class="divide-y divide-outline-variant/30">
                @forelse($recentLogs ?? [] as $log)
                    @php
                        $iconMap = [
                            'login' => ['icon' => 'login', 'bg' => 'bg-secondary-fixed', 'text' => 'text-secondary'],
                            'logout' => ['icon' => 'logout', 'bg' => 'bg-secondary-fixed', 'text' => 'text-secondary'],
                            'create' => ['icon' => 'add_circle', 'bg' => 'bg-green-100', 'text' => 'text-green-600'],
                            'update' => ['icon' => 'edit', 'bg' => 'bg-primary-fixed-dim', 'text' => 'text-primary'],
                            'delete' => ['icon' => 'delete', 'bg' => 'bg-error-container', 'text' => 'text-error'],
                            'approve' => ['icon' => 'verified_user', 'bg' => 'bg-primary-fixed-dim', 'text' => 'text-primary'],
                            'reject' => ['icon' => 'cancel', 'bg' => 'bg-error-container', 'text' => 'text-error'],
                        ];
                        $aksi = $log['aksi'] ?? 'update';
                        $iconData = $iconMap[$aksi] ?? ['icon' => 'info', 'bg' => 'bg-surface-container', 'text' => 'text-secondary'];
                    @endphp
                    <div class="p-lg hover:bg-surface-container-lowest transition-colors flex gap-md items-center py-lg">
                        <div class="w-10 h-10 rounded-full {{ $iconData['bg'] }} flex items-center justify-center {{ $iconData['text'] }} shrink-0">
                            <span class="material-symbols-outlined">{{ $iconData['icon'] }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <p class="text-body-md font-body-md text-on-surface">
                                    <span class="font-bold">{{ e($log['user']['full_name'] ?? 'Sistem') }}:</span> {{ e($log['deskripsi']) }}
                                </p>
                                <span class="text-label-sm font-label-sm text-secondary whitespace-nowrap ml-sm">{{ \Carbon\Carbon::parse($log['created_at'])->diffForHumans() }}</span>
                            </div>
                            <p class="text-body-sm font-body-sm text-on-surface-variant mt-xs">{{ $log['detail'] ?? 'Detail aktivitas tercatat dalam sistem.' }}</p>
                        </div>
                    </div>
                @empty
                    <div class="p-lg text-center">
                        <span class="material-symbols-outlined text-6xl text-on-surface-variant/30 mb-4 block">history</span>
                        <p class="text-body-md text-on-surface-variant">Belum ada aktivitas</p>
                    </div>
                @endforelse
            </div>
            <div class="px-lg py-md border-t border-outline-variant bg-surface-container-low/50">
                <a href="{{ route('activity-logs.index') }}" class="flex items-center justify-center gap-sm w-full py-md bg-primary text-on-primary rounded-xl font-label-md text-label-md hover:bg-primary-container transition-all active:scale-95 no-underline">
                    <span class="material-symbols-outlined text-[20px]">open_in_new</span>
                    Lihat Semua Aktivitas
                </a>
            </div>
        </section>
    </div>
</div>

<script>
    // Logika efek animasi pada elemen kartu
    document.querySelectorAll('.glass-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-4px)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
        });
        card.style.transition = 'transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease';
    });
</script>
   