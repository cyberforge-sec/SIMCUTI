@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Selamat datang kembali, {{ session('user_name') }}!</p>
</div>

@if(session('user_role') === 'admin')
    @include('dashboard.admin')
@elseif(session('user_role') === 'manager')
    @include('dashboard.manager')
@else
    @include('dashboard.karyawan')
@endif
@endsection

@push('scripts')
@if(session('user_role') === 'admin' && isset($monthlyTrend))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyTrend['labels'] ?? []),
                datasets: [{
                    label: 'Pengajuan Cuti',
                    data: @json($monthlyTrend['data'] ?? []),
                    borderColor: '#4F46E5',
                    backgroundColor: '#4F46E520',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#4F46E5',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: '#F3F4F6' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
</script>
@endif
@endpush
