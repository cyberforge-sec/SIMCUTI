<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Dashboard') - SIMCUTI</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Bootstrap Icons (icon font only, no CSS framework conflict) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Flatpickr (Date Picker) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-logo">
            <i class="bi bi-calendar-check-fill text-2xl text-primary-light"></i>
            <span class="sidebar-logo-text">SIMCUTI</span>
        </a>

        <ul class="px-4 list-none">
            <li class="nav-section-title">Menu Utama</li>

            <li class="mb-1">
                <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2 text-xl w-6 text-center"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>

            @if(session('user_role') !== 'karyawan')
            <li class="mb-1">
                <a href="{{ route('leave.pending') }}" class="nav-link {{ request()->routeIs('leave.pending') ? 'active' : '' }}">
                    <i class="bi bi-clock-history text-xl w-6 text-center"></i>
                    <span class="nav-text">Perlu Persetujuan</span>
                </a>
            </li>
            @endif

            @if(session('user_role') === 'manager')
            <li class="mb-1">
                <a href="{{ route('team') }}" class="nav-link {{ request()->routeIs('team') ? 'active' : '' }}">
                    <i class="bi bi-people text-xl w-6 text-center"></i>
                    <span class="nav-text">Anggota Tim</span>
                </a>
            </li>
            @endif

            @if(session('user_role') !== 'admin')
            <li class="nav-section-title">Cuti</li>

            <li class="mb-1">
                <a href="{{ route('leave.create') }}" class="nav-link {{ request()->routeIs('leave.create') ? 'active' : '' }}">
                    <i class="bi bi-plus-circle text-xl w-6 text-center"></i>
                    <span class="nav-text">Ajukan Cuti</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('leave.index') }}" class="nav-link {{ request()->routeIs('leave.index') ? 'active' : '' }}">
                    <i class="bi bi-list-ul text-xl w-6 text-center"></i>
                    <span class="nav-text">Pengajuan Saya</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('leave.history') }}" class="nav-link {{ request()->routeIs('leave.history') ? 'active' : '' }}">
                    <i class="bi bi-archive text-xl w-6 text-center"></i>
                    <span class="nav-text">Riwayat Cuti</span>
                </a>
            </li>

            @if(session('user_role') === 'manager')
            <li class="mb-1">
                <a href="{{ route('leave.employee-requests') }}" class="nav-link {{ request()->routeIs('leave.employee-requests') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-data text-xl w-6 text-center"></i>
                    <span class="nav-text">Pengajuan Karyawan</span>
                </a>
            </li>
            @endif
            @endif

            @if(session('user_role') === 'admin')
            <li class="nav-section-title">Master Data</li>

            <li class="mb-1">
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="bi bi-people text-xl w-6 text-center"></i>
                    <span class="nav-text">Pengguna</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('departments.index') }}" class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                    <i class="bi bi-building text-xl w-6 text-center"></i>
                    <span class="nav-text">Departemen</span>
                </a>
            </li>

            <li class="mb-1">
                <a href="{{ route('leave-types.index') }}" class="nav-link {{ request()->routeIs('leave-types.*') ? 'active' : '' }}">
                    <i class="bi bi-tags text-xl w-6 text-center"></i>
                    <span class="nav-text">Jenis Cuti</span>
                </a>
            </li>
            @endif

            @if(session('user_role') !== 'karyawan')
            <li class="nav-section-title">Laporan</li>

            <li class="mb-1">
                <a href="{{ route('reports.index') }}" class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-bar-graph text-xl w-6 text-center"></i>
                    <span class="nav-text">Laporan</span>
                </a>
            </li>
            @endif

            @if(session('user_role') === 'admin')
            <li class="mb-1">
                <a href="{{ route('activity-logs.index') }}" class="nav-link {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}">
                    <i class="bi bi-activity text-xl w-6 text-center"></i>
                    <span class="nav-text">Activity Logs</span>
                </a>
            </li>
            @endif
        </ul>
    </aside>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="header">
            <div class="flex items-center gap-4">
                <button class="bg-transparent border-none text-2xl text-dark cursor-pointer p-2 rounded-lg hover:bg-light-bg transition-all" onclick="toggleSidebar()">
                    <i class="bi bi-list"></i>
                </button>
            </div>

            <div class="flex items-center gap-4">
                <div x-data="{ open: false }" class="relative">
                    <div class="flex items-center gap-3 py-2 px-3 bg-transparent border border-border rounded-xl cursor-pointer transition-all hover:border-primary hover:bg-primary/5"
                         @click="open = !open" @click.away="open = false">
                        <img src="{{ session('profile_photo_url') ?? 'https://ui-avatars.com/api/?name=' . urlencode(session('user_name')) }}"
                             alt="User" class="w-10 h-10 rounded-full object-cover border-2 border-primary-light">
                        <div class="text-left">
                            <span class="font-semibold text-[0.9375rem] text-dark block">{{ session('user_name') }}</span>
                            <span class="text-xs text-text-muted">{{ ucfirst(session('user_role')) }}</span>
                        </div>
                        <i class="bi bi-chevron-down text-sm"></i>
                    </div>

                    <div x-show="open" x-transition
                         class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-border-light py-2 z-50">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-dark hover:bg-light-bg no-underline transition-colors">
                            <i class="bi bi-person"></i> Profil Saya
                        </a>
                        <a href="{{ route('settings') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-dark hover:bg-light-bg no-underline transition-colors">
                            <i class="bi bi-gear"></i> Pengaturan
                        </a>
                        <div class="border-t border-border-light my-1"></div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-danger hover:bg-red-50 border-none bg-transparent cursor-pointer transition-colors">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="p-8">
            @yield('content')
        </main>
    </div>

    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (collapsed) {
                sidebar.classList.add('collapsed');
            }
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        @if(session('success'))
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: '{{ session('success') }}',
                timer: 3000,
                showConfirmButton: false
            });
        @endif

        @if(session('error'))
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '{{ session('error') }}'
            });
        @endif
    </script>

    @stack('scripts')
</body>
</html>
