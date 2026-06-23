@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<div class="page-header">
    <h1 class="page-title">Notifikasi</h1>
    <p class="page-subtitle">Pusat notifikasi dan pemberitahuan</p>
</div>

<div class="card">
    <div class="card-body">
        @forelse($notifications ?? [] as $notif)
        <div class="d-flex align-items-start gap-3 p-3 border-bottom {{ empty($notif['is_read']) ? 'bg-light' : '' }}" id="notif-{{ $notif['id'] }}">
            <div class="flex-shrink-0">
                @php
                    $iconMap = [
                        'approved' => ['check-circle', 'success'],
                        'rejected' => ['x-circle', 'danger'],
                        'pending' => ['clock', 'warning'],
                        'info' => ['info-circle', 'info'],
                        'default' => ['bell', 'primary'],
                    ];
                    $type = $notif['type'] ?? 'default';
                    $icon = $iconMap[$type] ?? $iconMap['default'];
                @endphp
                <div class="rounded-circle d-flex align-items-center justify-content-center bg-{{ $icon[1] }} bg-opacity-10" style="width:40px;height:40px;">
                    <i class="bi bi-{{ $icon[0] }} text-{{ $icon[1] }}"></i>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between">
                    <strong>{{ $notif['title'] ?? 'Notifikasi' }}</strong>
                    <small class="text-muted">{{ isset($notif['created_at']) ? \Carbon\Carbon::parse($notif['created_at'])->diffForHumans() : '' }}</small>
                </div>
                <p class="mb-1 text-muted">{{ $notif['message'] ?? '-' }}</p>
                @if(empty($notif['is_read']))
                <button class="btn btn-sm btn-outline-primary mt-1" onclick="markRead('{{ $notif['id'] }}')">
                    <span class="material-symbols-outlined2 me-1">check</span>Tandai Dibaca
                </button>
                @else
                <small class="text-muted"><span class="material-symbols-outlined2-all">check</span> Sudah dibaca</small>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-5">
            <i class="bi bi-bell-slash" style="font-size: 3rem; color: #ccc;"></i>
            <p class="mt-2 mb-0 text-muted">Tidak ada notifikasi</p>
        </div>
        @endforelse
    </div>
</div>
@endsection

@push('scripts')
<script>
function markRead(id) {
    fetch(`/notifications/${id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById('notif-' + id);
            el.classList.remove('bg-light');
            el.querySelector('button').outerHTML = '<small class="text-muted"><span class="material-symbols-outlined2-all">check</span> Sudah dibaca</small>';
        }
    });
}
</script>
@endpush
