{-- Tampilan antarmuka (UI) halaman manager. --}
<!-- Welcome Header -->
<section class="flex flex-col gap-xs">
    <h2 class="font-headline-lg text-headline-lg text-on-surface">Selamat datang kembali, {{ session('user_name') }}.</h2>
    <p class="font-body-lg text-body-lg text-secondary">Kelola tim Anda hari ini.</p>
</section>

<div class="grid grid-cols-12 gap-lg">
    <!-- Left Column: Statistics & Pending -->
    <div class="col-span-12 lg:col-span-8 flex flex-col gap-lg h-full">
        <!-- Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-md">
            <!-- Card 1: Anggota Tim -->
            <div class="glass-card p-lg rounded-xl flex flex-col gap-md hover:translate-y-[-4px] transition-transform duration-300">
                <div class="w-12 h-12 rounded-xl bg-primary-container/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined">group</span>
                </div>
                <p class="font-label-md text-label-md text-secondary">Anggota Tim</p>
                <p class="font-headline-md text-headline-md text-on-surface mt-auto">{{ $teamSize ?? 0 }}</p>
            </div>

            <!-- Card 2: Perlu Persetujuan -->
            <div class="glass-card p-lg rounded-xl flex flex-col gap-md hover:translate-y-[-4px] transition-transform duration-300">
                <div class="w-12 h-12 rounded-xl bg-error-container/20 flex items-center justify-center text-error">
                    <span class="material-symbols-outlined">pending_actions</span>
                </div>
                <p class="font-label-md text-label-md text-secondary">Perlu Persetujuan</p>
                <p class="font-headline-md text-headline-md text-on-surface mt-auto">{{ $pendingCount ?? 0 }}</p>
            </div>

            <!-- Card 3: Cuti Hari Ini -->
            <div class="glass-card p-lg rounded-xl flex flex-col gap-md hover:translate-y-[-4px] transition-transform duration-300">
                <div class="w-12 h-12 rounded-xl bg-tertiary-fixed/30 flex items-center justify-center text-tertiary">
                    <span class="material-symbols-outlined">beach_access</span>
                </div>
                <p class="font-label-md text-label-md text-secondary">Cuti Hari Ini</p>
                <p class="font-headline-md text-headline-md text-on-surface mt-auto">{{ $onLeaveToday ?? 0 }}</p>
            </div>

            <!-- Card 4: Disetujui Bulan Ini -->
            <div class="glass-card p-lg rounded-xl flex flex-col gap-md hover:translate-y-[-4px] transition-transform duration-300">
                <div class="w-12 h-12 rounded-xl bg-secondary-container/30 flex items-center justify-center text-secondary">
                    <span class="material-symbols-outlined">verified</span>
                </div>
                <p class="font-label-md text-label-md text-secondary">Disetujui Bulan Ini</p>
                <p class="font-headline-md text-headline-md text-on-surface mt-auto">{{ $approvedThisMonth ?? 0 }}</p>
            </div>
        </div>

        <!-- Pending Approvals Section -->
        <div class="glass-card rounded-xl flex flex-col flex-1">
            <div class="p-lg border-b border-surface-variant flex justify-between items-center">
                <h3 class="font-headline-md text-headline-md">Menunggu Persetujuan</h3>
                <a href="{{ route('leave.pending') }}" class="text-primary font-label-md text-label-md hover:underline no-underline">Lihat Semua</a>
            </div>
            <div class="flex-1 flex flex-col">
                @forelse($pendingApprovals ?? [] as $approval)
                    <div class="p-lg border-b border-surface-variant/30 last:border-b-0 hover:bg-surface-container-low/50 transition-colors">
                        <div class="flex gap-md items-center">
                            <img src="{{ $approval['user']['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($approval['user']['full_name'] ?? 'U') }}"
                                 alt="Avatar" class="w-10 h-10 rounded-full shrink-0">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-label-md text-label-md text-on-surface">{{ e($approval['user']['full_name'] ?? '-') }}</p>
                                        <p class="font-label-sm text-label-sm text-secondary">
                                            {{ $approval['leave_type']['nama'] ?? '-' }} •
                                            {{ \Carbon\Carbon::parse($approval['tanggal_mulai'])->format('d M') }} -
                                            {{ \Carbon\Carbon::parse($approval['tanggal_selesai'])->format('d M Y') }}
                                        </p>
                                    </div>
                                    <span class="text-label-sm font-label-sm text-secondary whitespace-nowrap ml-sm">
                                        {{ \Carbon\Carbon::parse($approval['created_at'])->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-sm mt-sm">
                                    <span class="inline-flex items-center gap-xs px-sm py-xs bg-surface-container rounded-full text-label-sm text-primary">
                                        <span class="material-symbols-outlined" style="font-size: 14px;">schedule</span>
                                        {{ $approval['total_hari'] ?? 0 }} hari
                                    </span>
                                    <div class="flex gap-xs ml-auto">
                                        <button onclick="approveLeave('{{ $approval['id'] }}')"
                                                class="px-sm py-xs bg-green-500 text-white rounded-lg text-label-sm hover:bg-green-600 transition-colors">
                                            Setujui
                                        </button>
                                        <button onclick="rejectLeave('{{ $approval['id'] }}')"
                                                class="px-sm py-xs bg-error text-white rounded-lg text-label-sm hover:bg-error/90 transition-colors">
                                            Tolak
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex-1 flex flex-col items-center justify-center p-xxl text-center gap-md">
                        <div class="w-48 h-48 bg-surface-container rounded-full flex items-center justify-center relative">
                            <span class="material-symbols-outlined text-primary-container text-6xl opacity-20">check_circle</span>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <span class="material-symbols-outlined text-primary text-5xl">done_all</span>
                            </div>
                        </div>
                        <div>
                            <h4 class="font-headline-md text-headline-md mb-xs">Semua Beres!</h4>
                            <p class="font-body-md text-body-md text-secondary">Tidak ada pengajuan yang perlu diproses saat ini.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Right Column: Calendar & Fast Access -->
    <div class="col-span-12 lg:col-span-4 flex flex-col gap-lg h-full">
        <!-- Calendar Widget -->
        <div class="glass-card p-lg rounded-xl flex flex-col gap-md">
            <div class="flex justify-between items-center mb-md">
                <h3 class="font-label-md text-label-md text-secondary uppercase tracking-widest">Kalender</h3>
            </div>
            <div class="text-center font-bold text-on-surface mb-sm">{{ now()->format('F Y') }}</div>
            <div class="grid grid-cols-7 gap-y-sm text-center">
                <span class="text-xs font-bold text-secondary">Min</span>
                <span class="text-xs font-bold text-secondary">Sen</span>
                <span class="text-xs font-bold text-secondary">Sel</span>
                <span class="text-xs font-bold text-secondary">Rab</span>
                <span class="text-xs font-bold text-secondary">Kam</span>
                <span class="text-xs font-bold text-secondary">Jum</span>
                <span class="text-xs font-bold text-secondary">Sab</span>
                @php
                    $today = now();
                    $firstDay = $today->copy()->startOfMonth();
                    $daysInMonth = $today->daysInMonth;
                    $startDayOfWeek = $firstDay->dayOfWeek;
                @endphp
                @for($i = 0; $i < $startDayOfWeek; $i++)
                    <span class="py-sm"></span>
                @endfor
                @for($day = 1; $day <= $daysInMonth; $day++)
                    @if($day === $today->day)
                        <span class="py-sm font-label-md font-bold text-white bg-primary rounded-xl shadow-md">{{ $day }}</span>
                    @else
                        <span class="py-sm font-label-md text-on-surface hover:bg-surface-container-low rounded-lg cursor-pointer transition-colors">{{ $day }}</span>
                    @endif
                @endfor
            </div>
        </div>

        <!-- Who's on Leave Today -->
        <div class="glass-card p-lg rounded-xl flex flex-col gap-md">
            <h3 class="font-headline-md text-headline-md">Sedang Cuti Hari Ini</h3>
            <div class="flex flex-col gap-md">
                @php
                    $onLeaveList = collect($teamMembers ?? [])->filter(fn($m) => $m['is_on_leave'] ?? false);
                @endphp
                @if($onLeaveList->count() > 0)
                    @foreach($onLeaveList as $member)
                        <div class="flex items-center gap-md">
                            <div class="w-10 h-10 rounded-full bg-surface-container overflow-hidden">
                                <img class="w-full h-full object-cover"
                                     src="{{ $member['profile_photo_url'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($member['full_name'] ?? 'U') }}"
                                     alt="{{ $member['full_name'] }}">
                            </div>
                            <div class="flex-1">
                                <p class="font-label-md text-label-md">{{ $member['full_name'] }}</p>
                                <p class="font-label-sm text-label-sm text-secondary">{{ $member['sisa_cuti'] ?? 0 }} hari sisa</p>
                            </div>
                            <div class="px-sm py-xs bg-primary/10 text-primary rounded-full text-xs font-bold">Today</div>
                        </div>
                    @endforeach
                @else
                    <div class="flex flex-col items-center justify-center py-lg opacity-60 text-center">
                        <span class="material-symbols-outlined text-4xl mb-sm">group_off</span>
                        <p class="font-label-sm text-label-sm">Tidak ada anggota tim<br/>yang sedang cuti hari ini.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Access -->
        <div class="glass-card p-lg rounded-xl flex flex-col gap-md">
            <h3 class="font-headline-md text-headline-md">Akses Cepat</h3>
            <div class="grid grid-cols-2 gap-sm">
                <a href="{{ route('leave.pending') }}" class="flex flex-col items-center gap-sm p-lg bg-surface-container-low rounded-xl hover:bg-primary-container/10 transition-colors group no-underline">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">assignment_turned_in</span>
                    <span class="font-label-sm text-label-sm text-on-surface">Approvals</span>
                </a>
                <a href="{{ route('team') }}" class="flex flex-col items-center gap-sm p-lg bg-surface-container-low rounded-xl hover:bg-primary-container/10 transition-colors group no-underline">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">group</span>
                    <span class="font-label-sm text-label-sm text-on-surface">My Team</span>
                </a>
                <a href="{{ route('reports.index') }}" class="flex flex-col items-center gap-sm p-lg bg-surface-container-low rounded-xl hover:bg-primary-container/10 transition-colors group no-underline">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">assessment</span>
                    <span class="font-label-sm text-label-sm text-on-surface">Reports</span>
                </a>
                <a href="{{ route('leave.employee-requests') }}" class="flex flex-col items-center gap-sm p-lg bg-surface-container-low rounded-xl hover:bg-primary-container/10 transition-colors group no-underline">
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">event_note</span>
                    <span class="font-label-sm text-label-sm text-on-surface">Requests</span>
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function approveLeave(id) {
        Swal.fire({
            title: 'Setujui Pengajuan?',
            text: 'Pengajuan cuti akan disetujui',
            icon: 'question',
            input: 'textarea',
            inputLabel: 'Catatan (opsional)',
            inputPlaceholder: 'Tambahkan catatan approval...',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Setujui',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/leave/${id}/approve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ catatan: result.value })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                });
            }
        });
    }

    function rejectLeave(id) {
        Swal.fire({
            title: 'Tolak Pengajuan?',
            text: 'Harap berikan alasan penolakan',
            icon: 'warning',
            input: 'textarea',
            inputLabel: 'Alasan Penolakan',
            inputPlaceholder: 'Jelaskan alasan penolakan...',
            inputValidator: (value) => {
                if (!value) return 'Alasan penolakan wajib diisi!';
                if (value.length < 10) return 'Alasan minimal 10 karakter!';
            },
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Ya, Tolak',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/leave/${id}/reject`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ alasan: result.value })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Berhasil!', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error!', data.message, 'error');
                    }
                });
            }
        });
    }
</script>
@endpush
   