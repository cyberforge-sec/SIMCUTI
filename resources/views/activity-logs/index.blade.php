@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<!-- Page Header -->
<section class="flex flex-col md:flex-row md:items-center justify-between gap-md mb-lg">
    <div>
        <h2 class="text-headline-lg font-headline-lg text-on-background flex items-center gap-sm">
            <span class="material-symbols-outlined text-primary" style="font-size: 32px;">history</span>
            Log Aktivitas
        </h2>
        <p class="text-body-md font-body-md text-secondary mt-xs">Riwayat semua aktivitas pengguna dalam sistem</p>
    </div>
</section>

<!-- Filter Section -->
<section class="glass-card p-lg rounded-xl border border-outline-variant shadow-sm mb-lg">
    <div class="grid grid-cols-1 md:grid-cols-6 gap-md items-end">
        <div class="md:col-span-1">
            <label class="text-label-sm font-label-md text-secondary mb-xs block">Aksi</label>
            <select id="filterAksi" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                <option value="">Semua</option>
                <option value="create">Create</option>
                <option value="update">Update</option>
                <option value="delete">Delete</option>
                <option value="approve">Approve</option>
                <option value="reject">Reject</option>
                <option value="login">Login</option>
                <option value="export">Export</option>
                <option value="view">View</option>
            </select>
        </div>
        <div class="md:col-span-1">
            <label class="text-label-sm font-label-md text-secondary mb-xs block">Model</label>
            <select id="filterModel" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
                <option value="">Semua</option>
                <option value="leave_request">Leave Request</option>
                <option value="profile">Profile</option>
                <option value="department">Department</option>
                <option value="leave_type">Leave Type</option>
            </select>
        </div>
        <div class="md:col-span-1">
            <label class="text-label-sm font-label-md text-secondary mb-xs block">Dari Tanggal</label>
            <input type="date" id="filterDateFrom" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
        </div>
        <div class="md:col-span-1">
            <label class="text-label-sm font-label-md text-secondary mb-xs block">Sampai Tanggal</label>
            <input type="date" id="filterDateTo" class="w-full px-md py-sm bg-surface-container-lowest border border-outline-variant rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all text-body-sm">
        </div>
        <div class="md:col-span-2 flex items-end gap-sm">
            <button onclick="loadLogs()" class="flex items-center gap-xs px-lg py-sm bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-all active:scale-95">
                <span class="material-symbols-outlined" style="font-size: 18px;">search</span>
                Filter
            </button>
            <button onclick="resetFilters()" class="flex items-center gap-xs px-lg py-sm border border-outline-variant rounded-xl font-label-md text-on-surface-variant hover:bg-surface-container-low transition-all active:scale-95">
                Reset
            </button>
        </div>
    </div>
</section>

<!-- Table Container -->
<section class="bg-surface rounded-xl border border-outline-variant shadow-sm overflow-hidden">
    <div class="p-lg border-b border-outline-variant flex items-center justify-between">
        <div class="flex items-center gap-md">
            <h3 class="text-label-md font-label-md text-on-background">Riwayat Log</h3>
            <span id="totalBadge" class="px-2 py-1 bg-primary/10 text-primary text-label-sm rounded-full">Memuat...</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-surface-container-lowest text-secondary uppercase text-[11px] tracking-wider font-semibold">
                    <th class="px-lg py-md border-b border-outline-variant">Waktu</th>
                    <th class="px-lg py-md border-b border-outline-variant">User</th>
                    <th class="px-lg py-md border-b border-outline-variant">Aksi</th>
                    <th class="px-lg py-md border-b border-outline-variant">Deskripsi</th>
                    <th class="px-lg py-md border-b border-outline-variant">Model</th>
                    <th class="px-lg py-md border-b border-outline-variant">IP Address</th>
                </tr>
            </thead>
            <tbody id="logsBody" class="divide-y divide-outline-variant">
                <tr>
                    <td colspan="6" class="px-lg py-12 text-center">
                        <div class="flex flex-col items-center gap-sm">
                            <span class="material-symbols-outlined text-4xl text-primary animate-spin">progress_activity</span>
                            <p class="text-body-sm text-secondary">Memuat data...</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="p-lg bg-surface-container-lowest border-t border-outline-variant flex items-center justify-between">
        <p id="totalInfo" class="text-body-sm font-body-sm text-secondary">-</p>
        <div class="flex items-center gap-xs">
            <button id="btnPrev" onclick="prevPage()" disabled class="w-8 h-8 flex items-center justify-center border border-outline-variant rounded-lg disabled:opacity-40 hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined" style="font-size: 20px;">chevron_left</span>
            </button>
            <button id="btnNext" onclick="nextPage()" class="w-8 h-8 flex items-center justify-center border border-outline-variant rounded-lg hover:bg-surface-container-low transition-colors">
                <span class="material-symbols-outlined" style="font-size: 20px;">chevron_right</span>
            </button>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
let currentOffset = 0;
const pageSize = 50;
let totalLogs = 0;

function loadLogs() {
    const params = new URLSearchParams();
    params.set('limit', pageSize);
    params.set('offset', currentOffset);

    const aksi = document.getElementById('filterAksi').value;
    const model = document.getElementById('filterModel').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    const dateTo = document.getElementById('filterDateTo').value;

    if (aksi) params.set('aksi', aksi);
    if (model) params.set('model_type', model);
    if (dateFrom) params.set('date_from', dateFrom);
    if (dateTo) params.set('date_to', dateTo);

    document.getElementById('logsBody').innerHTML = `
        <tr><td colspan="6" class="px-lg py-12 text-center">
            <div class="flex flex-col items-center gap-sm">
                <span class="material-symbols-outlined text-4xl text-primary animate-spin">progress_activity</span>
                <p class="text-body-sm text-secondary">Memuat...</p>
            </div>
        </td></tr>`;

    fetch(`/activity-logs/data?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            totalLogs = data.total || 0;
            renderLogs(data.data || []);
            updatePagination();
        })
        .catch(() => {
            document.getElementById('logsBody').innerHTML = `
                <tr><td colspan="6" class="px-lg py-12 text-center">
                    <div class="flex flex-col items-center gap-md">
                        <span class="material-symbols-outlined text-4xl text-error">error</span>
                        <p class="text-body-sm text-error">Gagal memuat data</p>
                    </div>
                </td></tr>`;
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function renderLogs(logs) {
    const tbody = document.getElementById('logsBody');
    document.getElementById('totalBadge').textContent = `${totalLogs} log`;

    if (logs.length === 0) {
        tbody.innerHTML = `
            <tr><td colspan="6" class="px-lg py-12 text-center">
                <div class="flex flex-col items-center gap-md">
                    <span class="material-symbols-outlined text-6xl text-on-surface-variant/30">inbox</span>
                    <p class="text-body-md text-on-surface-variant">Tidak ada data</p>
                </div>
            </td></tr>`;
        return;
    }

    const aksiStyles = {
        create: { bg: 'bg-green-100', text: 'text-green-600', icon: 'add_circle' },
        update: { bg: 'bg-primary/10', text: 'text-primary', icon: 'edit' },
        delete: { bg: 'bg-error-container', text: 'text-error', icon: 'delete' },
        approve: { bg: 'bg-green-100', text: 'text-green-600', icon: 'check_circle' },
        reject: { bg: 'bg-error-container', text: 'text-error', icon: 'cancel' },
        login: { bg: 'bg-secondary-container', text: 'text-on-secondary-container', icon: 'login' },
        export: { bg: 'bg-warning/10', text: 'text-warning', icon: 'download' },
        view: { bg: 'bg-surface-container-highest', text: 'text-secondary', icon: 'visibility' }
    };

    tbody.innerHTML = logs.map(log => {
        const style = aksiStyles[log.aksi] || aksiStyles.view;
        return `
            <tr class="hover:bg-primary-container/5 transition-colors">
                <td class="px-lg py-md text-body-sm text-secondary">${log.created_at ? escapeHtml(new Date(log.created_at).toLocaleString('id-ID')) : '-'}</td>
                <td class="px-lg py-md text-body-sm font-label-md text-on-background">${escapeHtml(log.user_name || 'Unknown')}</td>
                <td class="px-lg py-md">
                    <span class="inline-flex items-center gap-xs px-3 py-1 ${style.bg} ${style.text} text-label-sm font-label-sm rounded-full">
                        <span class="material-symbols-outlined" style="font-size: 14px;">${style.icon}</span>
                        ${escapeHtml(log.aksi || '-')}
                    </span>
                </td>
                <td class="px-lg py-md text-body-sm text-on-surface-variant">${escapeHtml(log.deskripsi || '-')}</td>
                <td class="px-lg py-md text-body-sm text-secondary">${escapeHtml(log.model_type ? log.model_type + (log.model_id ? ' #' + log.model_id.substring(0,8) : '') : '-')}</td>
                <td class="px-lg py-md text-body-sm text-secondary">${escapeHtml(log.ip_address || '-')}</td>
            </tr>`;
    }).join('');
}

function updatePagination() {
    const start = currentOffset + 1;
    const end = Math.min(currentOffset + pageSize, totalLogs);
    document.getElementById('totalInfo').textContent = `Menampilkan ${start} - ${end} dari ${totalLogs} log`;
    document.getElementById('btnPrev').disabled = currentOffset <= 0;
    document.getElementById('btnNext').disabled = currentOffset + pageSize >= totalLogs;
}

function prevPage() { currentOffset = Math.max(0, currentOffset - pageSize); loadLogs(); }
function nextPage() { currentOffset += pageSize; loadLogs(); }
function resetFilters() {
    document.getElementById('filterAksi').value = '';
    document.getElementById('filterModel').value = '';
    document.getElementById('filterDateFrom').value = '';
    document.getElementById('filterDateTo').value = '';
    currentOffset = 0;
    loadLogs();
}

document.addEventListener('DOMContentLoaded', loadLogs);
</script>
@endpush
  