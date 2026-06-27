@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
<div class="space-y-lg">
    <!-- Page Header Section -->
    <section class="flex flex-col md:flex-row md:items-center justify-between gap-md">
        <div>
            <h2 class="text-headline-lg font-headline-lg text-on-background">Pengguna</h2>
            <p class="text-body-md font-body-md text-secondary">Kelola data pengguna sistem</p>
        </div>
        <a href="{{ route('users.create') }}" class="flex items-center gap-sm bg-primary text-on-primary px-lg py-md rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:bg-primary-container transition-all active:scale-95 no-underline">
            <span class="material-symbols-outlined">add</span>
            Tambah Pengguna
        </a>
    </section>

    <!-- Stats Overview (Asymmetric/Bento-lite) -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-lg">
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-primary/10 rounded-full flex items-center justify-center text-primary">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">group</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Total Pengguna</p>
                <p class="text-headline-md font-headline-md text-on-background">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-secondary-container rounded-full flex items-center justify-center text-on-secondary-container">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">verified_user</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Aktif</p>
                <p class="text-headline-md font-headline-md text-on-background">{{ $stats['active'] }}</p>
            </div>
        </div>
        <div class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm flex items-center gap-lg">
            <div class="w-14 h-14 bg-error-container/20 rounded-full flex items-center justify-center text-error">
                <span class="material-symbols-outlined" style="font-size: 32px; font-variation-settings: 'FILL' 1;">block</span>
            </div>
            <div>
                <p class="text-body-sm font-body-sm text-secondary">Nonaktif</p>
                <p class="text-headline-md font-headline-md text-on-background">{{ $stats['inactive'] }}</p>
            </div>
        </div>
    </section>

    <!-- Table Container -->
    <section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
        <!-- Table Header with Search, Filter, Export -->
        <div class="p-lg border-b border-outline-variant flex flex-col md:flex-row md:items-center justify-between gap-md">
            <div class="flex items-center gap-md">
                <h3 class="text-label-md font-label-md text-on-background">Daftar Pengguna</h3>
                <div class="px-2 py-1 bg-surface-container-highest text-secondary text-label-sm font-label-sm rounded-lg">
                    Showing {{ $paginatedUsers->count() }} results
                </div>
            </div>
            <div class="flex items-center gap-sm flex-wrap">
                <!-- Search -->
                <form action="{{ route('users.index') }}" method="GET" class="relative" id="searchForm">
                    @if(request('role'))
                        <input type="hidden" name="role" value="{{ request('role') }}">
                    @endif
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                    <span class="absolute inset-y-0 left-3 flex items-center text-outline pointer-events-none">
                        <span class="material-symbols-outlined text-[20px]">search</span>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="pl-10 pr-4 py-2 bg-surface-container-low border-none rounded-xl focus:ring-2 focus:ring-primary/20 w-64 text-body-sm font-body-sm"
                           placeholder="Search data..."
                           id="searchInput">
                </form>

                <!-- Filter Dropdown -->
                <div class="relative" id="filterWrapper">
                    <button type="button" id="filterBtn" class="flex items-center gap-xs px-md py-sm border border-outline-variant rounded-lg text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-[18px]">filter_list</span>
                        Filter
                        @if(request('role') || request('status'))
                            <span class="w-2 h-2 bg-primary rounded-full"></span>
                        @endif
                    </button>
                    <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg z-20 p-md space-y-md">
                        <form action="{{ route('users.index') }}" method="GET" class="space-y-md">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <div>
                                <label class="block text-label-sm font-label-sm text-on-surface mb-xs">Role</label>
                                <select name="role" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-body-sm focus:ring-2 focus:ring-primary/20 outline-none">
                                    <option value="">Semua Role</option>
                                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="manager" {{ request('role') === 'manager' ? 'selected' : '' }}>Manager</option>
                                    <option value="karyawan" {{ request('role') === 'karyawan' ? 'selected' : '' }}>Karyawan</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-label-sm font-label-sm text-on-surface mb-xs">Status</label>
                                <select name="status" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-lg text-body-sm focus:ring-2 focus:ring-primary/20 outline-none">
                                    <option value="">Semua Status</option>
                                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                                </select>
                            </div>
                            <div class="flex gap-sm">
                                <button type="submit" class="flex-1 bg-primary text-on-primary px-md py-sm rounded-lg text-label-sm font-label-sm hover:opacity-90 transition-all">
                                    Terapkan
                                </button>
                                <a href="{{ route('users.index') }}" class="flex-1 text-center border border-outline-variant px-md py-sm rounded-lg text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low transition-all no-underline">
                                    Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Export -->
                <button type="button" id="exportBtn" class="flex items-center gap-xs px-md py-sm border border-outline-variant rounded-lg text-label-sm font-label-sm text-on-surface-variant hover:bg-surface-container-low transition-colors">
                    <span class="material-symbols-outlined text-[18px]">download</span>
                    Export
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-surface-container-lowest text-secondary uppercase text-[11px] tracking-wider font-semibold">
                        <th class="px-lg py-md border-b border-outline-variant w-16">No</th>
                        <th class="px-lg py-md border-b border-outline-variant">Nama</th>
                        <th class="px-lg py-md border-b border-outline-variant">Email</th>
                        <th class="px-lg py-md border-b border-outline-variant">Role</th>
                        <th class="px-lg py-md border-b border-outline-variant">Departemen</th>
                        <th class="px-lg py-md border-b border-outline-variant">Status</th>
                        <th class="px-lg py-md border-b border-outline-variant text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                    @forelse($paginatedUsers as $i => $user)
                    <tr class="hover:bg-primary-container/5 transition-colors group">
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">{{ str_pad($paginatedUsers->firstItem() + $i, 2, '0', STR_PAD_LEFT) }}</td>
                        <td class="px-lg py-md">
                            <div class="flex items-center gap-md">
                                @php
                                    $initials = collect(explode(' ', $user['full_name'] ?? 'U'))
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
                                <span class="text-body-sm font-label-md text-on-background">{{ e($user['full_name'] ?? '-') }}</span>
                            </div>
                        </td>
                        <td class="px-lg py-md text-body-sm font-body-sm text-secondary">{{ $user['email'] ?? '-' }}</td>
                        <td class="px-lg py-md">
                            @php
                                $roleStyles = [
                                    'admin' => 'bg-primary/10 text-primary',
                                    'manager' => 'bg-secondary-container text-on-secondary-container',
                                    'karyawan' => 'bg-surface-container-highest text-secondary'
                                ];
                            @endphp
                            <span class="px-3 py-1 {{ $roleStyles[$user['role'] ?? ''] ?? 'bg-surface-container-highest text-secondary' }} text-label-sm font-label-sm rounded-full">
                                {{ ucfirst($user['role'] ?? '-') }}
                            </span>
                        </td>
                        <td class="px-lg py-md text-body-sm font-body-sm text-on-surface-variant">{{ $user['department_name'] ?? '-' }}</td>
                        <td class="px-lg py-md">
                            @if(!empty($user['is_active']))
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
                                <a href="{{ route('users.edit', $user['id']) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-primary-container/10 hover:text-primary transition-colors" title="Edit">
                                    <span class="material-symbols-outlined text-[20px]">edit</span>
                                </a>
                                <form action="{{ route('users.toggle-active', $user['id']) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-primary-container/10 hover:text-primary transition-colors" title="{{ !empty($user['is_active']) ? 'Nonaktifkan' : 'Aktifkan' }}">
                                        <span class="material-symbols-outlined text-[20px]">
                                            {{ !empty($user['is_active']) ? 'person_off' : 'person' }}
                                        </span>
                                    </button>
                                </form>
                                <button type="button" class="w-8 h-8 rounded-lg flex items-center justify-center text-secondary hover:bg-error-container/20 hover:text-error transition-colors" onclick="deleteUser('{{ $user['id'] }}', '{{ addslashes($user['full_name']) }}')" title="Hapus">
                                    <span class="material-symbols-outlined text-[20px]">delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-lg py-12 text-center">
                            <div class="flex flex-col items-center gap-md">
                                <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">inbox</span>
                                <p class="text-body-md font-body-md text-on-surface-variant">Belum ada data pengguna</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($paginatedUsers->hasPages())
        <!-- Pagination -->
        <div class="p-lg bg-surface-container-lowest border-t border-outline-variant flex items-center justify-between">
            <p class="text-body-sm font-body-sm text-secondary">
                Page {{ $paginatedUsers->currentPage() }} of {{ $paginatedUsers->lastPage() }}
            </p>
            <div class="flex items-center gap-xs">
                {{-- Previous --}}
                @if($paginatedUsers->onFirstPage())
                    <button class="w-8 h-8 flex items-center justify-center border border-outline-variant rounded-lg opacity-50" disabled>
                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                    </button>
                @else
                    <a href="{{ $paginatedUsers->previousPageUrl() }}" class="w-8 h-8 flex items-center justify-center border border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-[20px]">chevron_left</span>
                    </a>
                @endif

                {{-- Page Numbers --}}
                @foreach($paginatedUsers->getUrlRange(1, $paginatedUsers->lastPage()) as $page => $url)
                    @if($page == $paginatedUsers->currentPage())
                        <span class="w-8 h-8 flex items-center justify-center bg-primary text-on-primary rounded-lg font-label-sm text-label-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="w-8 h-8 flex items-center justify-center hover:bg-surface-container-low rounded-lg font-label-sm text-label-sm no-underline text-on-background">{{ $page }}</a>
                    @endif
                @endforeach

                {{-- Next --}}
                @if($paginatedUsers->hasMorePages())
                    <a href="{{ $paginatedUsers->nextPageUrl() }}" class="w-8 h-8 flex items-center justify-center border border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                    </a>
                @else
                    <button class="w-8 h-8 flex items-center justify-center border border-outline-variant rounded-lg opacity-50" disabled>
                        <span class="material-symbols-outlined text-[20px]">chevron_right</span>
                    </button>
                @endif
            </div>
        </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script>
// Fungsi buka/tutup menu penyaring (filter)
const filterBtn = document.getElementById('filterBtn');
const filterDropdown = document.getElementById('filterDropdown');

if (filterBtn && filterDropdown) {
    filterBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        filterDropdown.classList.toggle('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
            filterDropdown.classList.add('hidden');
        }
    });
}

// Fungsi pencarian otomatis dengan jeda pengetikan (debounce)
const searchInput = document.getElementById('searchInput');
const searchForm = document.getElementById('searchForm');
let searchTimeout;

if (searchInput && searchForm) {
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchForm.submit();
        }, 500);
    });
}

// Fungsi unduh data ke format CSV
const exportBtn = document.getElementById('exportBtn');
if (exportBtn) {
    exportBtn.addEventListener('click', () => {
        const table = document.querySelector('table');
        if (!table) return;

        let csv = [];
        const rows = table.querySelectorAll('tr');

        rows.forEach(row => {
            const cols = row.querySelectorAll('th, td');
            const rowData = [];
            cols.forEach(col => {
                let text = col.innerText.trim().replace(/"/g, '""');
                rowData.push('"' + text + '"');
            });
            csv.push(rowData.join(','));
        });

        const csvContent = csv.join('\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'pengguna_simcuti_' + new Date().toISOString().slice(0, 10) + '.csv';
        link.click();
    });
}

// Efek interaksi animasi ringan
document.querySelectorAll('button').forEach(button => {
    button.addEventListener('mousedown', () => {
        button.classList.add('scale-95');
    });
    button.addEventListener('mouseup', () => {
        button.classList.remove('scale-95');
    });
    button.addEventListener('mouseleave', () => {
        button.classList.remove('scale-95');
    });
});

// Fungsi untuk menghapus pengguna
function deleteUser(id, nama) {
    Swal.fire({
        title: 'Nonaktifkan User?',
        text: `User "${nama}" akan dinonaktifkan.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        confirmButtonText: 'Ya!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = `/users/${id}`;
            form.innerHTML = `<input type="hidden" name="_method" value="DELETE"><input type="hidden" name="_token" value="${csrfToken}">`;
            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>
@endpush
 