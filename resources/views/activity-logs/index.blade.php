@extends('layouts.app')

@section('title', 'Activity Logs')

@section('content')
<div class="page-header">
    <h1 class="page-title">Activity Logs</h1>
    <p class="page-subtitle">Riwayat aktivitas pengguna dalam sistem</p>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-3">
        <div class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Aksi</label>
                <select id="filterAksi" class="form-select form-select-sm">
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
            <div class="col-md-2">
                <label class="form-label small">Model</label>
                <select id="filterModel" class="form-select form-select-sm">
                    <option value="">Semua</option>
                    <option value="leave_request">Leave Request</option>
                    <option value="profile">Profile</option>
                    <option value="department">Department</option>
                    <option value="leave_type">Leave Type</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Dari Tanggal</label>
                <input type="date" id="filterDateFrom" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Sampai Tanggal</label>
                <input type="date" id="filterDateTo" class="form-control form-control-sm">
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary" onclick="loadLogs()"><i class="bi bi-search me-1"></i>Filter</button>
                <button class="btn btn-sm btn-outline-secondary" onclick="resetFilters()">Reset</button>
            </div>
        </div>
    </div>
</div>

<!-- Logs Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm" id="logsTable">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Aksi</th>
                        <th>Deskripsi</th>
                        <th>Model</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="logsBody">
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="spinner-border spinner-border-sm text-primary"></div> Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-2">
            <small class="text-muted" id="totalInfo">-</small>
            <div>
                <button class="btn btn-sm btn-outline-secondary" id="btnPrev" onclick="prevPage()" disabled>
                    <i class="bi bi-chevron-left"></i> Prev
                </button>
                <button class="btn btn-sm btn-outline-secondary" id="btnNext" onclick="nextPage()">
                    Next <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>
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

    document.getElementById('logsBody').innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Memuat...</td></tr>';

    fetch(`/activity-logs/data?${params.toString()}`)
        .then(r => r.json())
        .then(data => {
            totalLogs = data.total || 0;
            renderLogs(data.data || []);
            updatePagination();
        })
        .catch(() => {
            document.getElementById('logsBody').innerHTML = '<tr><td colspan="6" class="text-center text-danger">Gagal memuat data</td></tr>';
        });
}

function renderLogs(logs) {
    const tbody = document.getElementById('logsBody');
    if (logs.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><i class="bi bi-inbox" style="font-size:2rem;color:#ccc;"></i><br>Tidak ada data</td></tr>';
        return;
    }

    const aksiColors = {
        create: 'success', update: 'primary', delete: 'danger',
        approve: 'success', reject: 'danger', login: 'info',
        export: 'warning', view: 'secondary'
    };

    tbody.innerHTML = logs.map(log => `
        <tr>
            <td><small>${log.created_at ? new Date(log.created_at).toLocaleString('id-ID') : '-'}</small></td>
            <td>${log.user_name || 'Unknown'}</td>
            <td><span class="badge bg-${aksiColors[log.aksi] || 'secondary'}">${log.aksi || '-'}</span></td>
            <td>${log.deskripsi || '-'}</td>
            <td><small class="text-muted">${log.model_type ? log.model_type + (log.model_id ? ' #' + log.model_id.substring(0,8) : '') : '-'}</small></td>
            <td><small class="text-muted">${log.ip_address || '-'}</small></td>
        </tr>
    `).join('');
}

function updatePagination() {
    document.getElementById('totalInfo').textContent = `Total: ${totalLogs} log | Menampilkan ${currentOffset + 1} - ${Math.min(currentOffset + pageSize, totalLogs)}`;
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

// Load on page ready
document.addEventListener('DOMContentLoaded', loadLogs);
</script>
@endpush
