<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - SIMCUTI</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Material Symbols Outlined -->
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-surface-variant": "#444655",
                        "tertiary-fixed": "#e0e3e5",
                        "on-secondary-fixed": "#0b1c30",
                        "on-primary-container": "#c9ceff",
                        "surface-container-low": "#f2f3ff",
                        "secondary": "#505f76",
                        "outline-variant": "#c5c5d7",
                        "on-primary-fixed": "#000f5c",
                        "primary": "#032bbe",
                        "on-tertiary-fixed-variant": "#444749",
                        "tertiary-fixed-dim": "#c4c7c9",
                        "on-primary": "#ffffff",
                        "surface-container-lowest": "#ffffff",
                        "outline": "#757686",
                        "surface-dim": "#d2d9f4",
                        "surface": "#faf8ff",
                        "primary-fixed": "#dee0ff",
                        "error-container": "#ffdad6",
                        "on-secondary-fixed-variant": "#38485d",
                        "primary-container": "#2e49d5",
                        "secondary-container": "#d0e1fb",
                        "background": "#faf8ff",
                        "inverse-primary": "#bac3ff",
                        "surface-bright": "#faf8ff",
                        "secondary-fixed": "#d3e4fe",
                        "on-error": "#ffffff",
                        "surface-variant": "#dae2fd",
                        "primary-fixed-dim": "#bac3ff",
                        "surface-tint": "#344eda",
                        "on-primary-fixed-variant": "#0f31c2",
                        "tertiary-container": "#575a5c",
                        "on-background": "#131b2e",
                        "on-surface": "#131b2e",
                        "tertiary": "#3f4345",
                        "inverse-on-surface": "#eef0ff",
                        "on-error-container": "#93000a",
                        "secondary-fixed-dim": "#b7c8e1",
                        "on-tertiary-container": "#cfd1d3",
                        "on-secondary": "#ffffff",
                        "surface-container-high": "#e2e7ff",
                        "surface-container-highest": "#dae2fd",
                        "surface-container": "#eaedff",
                        "on-tertiary-fixed": "#191c1e",
                        "inverse-surface": "#283044",
                        "on-tertiary": "#ffffff",
                        "error": "#ba1a1a",
                        "on-secondary-container": "#54647a"
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    },
                    spacing: {
                        sm: "8px",
                        md: "16px",
                        gutter: "24px",
                        "container-max": "1280px",
                        lg: "24px",
                        xl: "32px",
                        unit: "4px",
                        xxl: "48px",
                        xs: "4px"
                    },
                    fontFamily: {
                        "body-lg": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Plus Jakarta Sans"],
                        "headline-md": ["Plus Jakarta Sans"],
                        "headline-xl": ["Plus Jakarta Sans"],
                        "body-sm": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "label-sm": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"]
                    },
                    fontSize: {
                        "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }],
                        "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "headline-lg-mobile": ["24px", { lineHeight: "32px", fontWeight: "700" }],
                        "headline-md": ["24px", { lineHeight: "32px", fontWeight: "600" }],
                        "headline-xl": ["40px", { lineHeight: "48px", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "body-sm": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                        "label-md": ["14px", { lineHeight: "20px", letterSpacing: "0.01em", fontWeight: "600" }],
                        "label-sm": ["12px", { lineHeight: "16px", fontWeight: "500" }],
                        "headline-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }]
                    }
                }
            }
        }
    </script>

    <style>
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #faf8ff;
        }
        .swal2-popup {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
    </style>

    @stack('styles')
</head>
<body class="bg-background text-on-background antialiased overflow-x-hidden">
    <!-- Layout Shell -->
    <div class="flex min-h-screen">
        <!-- SideNavBar -->
        <aside id="sidebar" class="hidden md:flex h-screen w-72 flex-col fixed left-0 top-0 bg-surface border-r border-outline-variant shadow-sm z-50 transition-all duration-300">
            <div class="flex flex-col h-full p-md gap-sm">
                <!-- Brand Anchor -->
                <div class="px-lg py-xl mb-md text-center">
                    <h1 class="text-headline-md font-headline-md font-bold text-primary">SIMCUTI Adiva</h1>
                    <p class="text-body-sm font-body-sm text-secondary">
                        @if(session('user_role') === 'admin')
                            Admin Portal
                        @elseif(session('user_role') === 'manager')
                            Manager Portal
                        @else
                            Karyawan Portal
                        @endif
                    </p>
                </div>

                <!-- Main Nav Links -->
                <nav class="flex-1 space-y-xs overflow-y-auto">
                    <!-- Dashboard -->
                    <a class="{{ request()->routeIs('dashboard') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl font-semibold flex items-center gap-md px-lg py-md" href="{{ route('dashboard') }}">
                        <span class="material-symbols-outlined">dashboard</span>
                        <span class="text-label-md font-label-md nav-text">Dashboard</span>
                    </a>

                    @if(session('user_role') === 'admin')
                        <!-- Admin Menu -->
                        <a class="{{ request()->routeIs('users.*') && !request()->routeIs('users.create') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('users.index') }}">
                            <span class="material-symbols-outlined">group</span>
                            <span class="text-label-md font-label-md nav-text">Pengguna</span>
                        </a>
                        <a class="{{ request()->routeIs('users.create') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('users.create') }}">
                            <span class="material-symbols-outlined">person_add</span>
                            <span class="text-label-md font-label-md nav-text">Tambah Pengguna</span>
                        </a>
                        <a class="{{ request()->routeIs('departments.*') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('departments.index') }}">
                            <span class="material-symbols-outlined">domain</span>
                            <span class="text-label-md font-label-md nav-text">Departemen</span>
                        </a>
                        <a class="{{ request()->routeIs('leave-types.*') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('leave-types.index') }}">
                            <span class="material-symbols-outlined">category</span>
                            <span class="text-label-md font-label-md nav-text">Jenis Cuti</span>
                        </a>
                        <a class="{{ request()->routeIs('activity-logs.*') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('activity-logs.index') }}">
                            <span class="material-symbols-outlined">history</span>
                            <span class="text-label-md font-label-md nav-text">Log Aktivitas</span>
                        </a>
                    @endif

                    @if(session('user_role') !== 'admin')
                        <!-- Cuti Menu -->
                        <a class="{{ request()->routeIs('leave.create') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('leave.create') }}">
                            <span class="material-symbols-outlined">add_circle</span>
                            <span class="text-label-md font-label-md nav-text">Ajukan Cuti</span>
                        </a>
                        <a class="{{ request()->routeIs('leave.index') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('leave.index') }}">
                            <span class="material-symbols-outlined">event_note</span>
                            <span class="text-label-md font-label-md nav-text">Pengajuan Saya</span>
                        </a>
                        <a class="{{ request()->routeIs('leave.history') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('leave.history') }}">
                            <span class="material-symbols-outlined">history</span>
                            <span class="text-label-md font-label-md nav-text">Riwayat</span>
                        </a>
                    @endif

                    @if(session('user_role') === 'manager')
                        <a class="{{ request()->routeIs('leave.pending') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('leave.pending') }}">
                            <span class="material-symbols-outlined">pending_actions</span>
                            <span class="text-label-md font-label-md nav-text">Perlu Persetujuan</span>
                        </a>
                        <a class="{{ request()->routeIs('team') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('team') }}">
                            <span class="material-symbols-outlined">groups</span>
                            <span class="text-label-md font-label-md nav-text">Tim Saya</span>
                        </a>
                    @endif

                    @if(session('user_role') !== 'karyawan')
                        <a class="{{ request()->routeIs('reports.*') ? 'bg-primary text-on-primary' : 'text-secondary hover:bg-secondary-container transition-all' }} rounded-xl flex items-center gap-md px-lg py-md" href="{{ route('reports.index') }}">
                            <span class="material-symbols-outlined">analytics</span>
                            <span class="text-label-md font-label-md nav-text">Laporan</span>
                        </a>
                    @endif
                </nav>

                <!-- Footer Nav -->
                <div class="mt-auto border-t border-outline-variant pt-md">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-secondary flex items-center gap-md px-lg py-md hover:bg-error-container hover:text-error transition-all rounded-lg w-full bg-transparent border-none cursor-pointer text-left">
                            <span class="material-symbols-outlined">logout</span>
                            <span class="text-label-md font-label-md nav-text">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content Canvas -->
        <main class="flex-1 md:ml-72 flex flex-col min-h-screen">
            <!-- Top Header -->
            <header class="bg-surface/80 backdrop-blur-xl border-b border-outline-variant shadow-sm sticky top-0 z-40">
                <div class="flex justify-between items-center w-full px-lg py-md max-w-container-max mx-auto h-20">
                    <!-- Mobile Menu Toggle -->
                    <button id="mobileMenuBtn" class="md:hidden p-sm text-on-surface-variant hover:bg-primary-container/10 transition-colors rounded-full">
                        <span class="material-symbols-outlined">menu</span>
                    </button>

                    <div class="flex items-center gap-lg ml-auto">
                        <!-- Profile Dropdown -->
                        <div class="relative" id="profileDropdownWrapper">
                            <button id="profileDropdownBtn" class="flex items-center gap-md pl-lg border-l border-outline-variant hover:bg-surface-container-low/50 rounded-lg px-sm py-xs transition-all">
                                <div class="text-right hidden sm:block">
                                    <p class="text-label-md font-label-md text-on-surface">{{ session('user_name') }}</p>
                                    <p class="text-label-sm font-label-sm text-secondary">{{ ucfirst(session('user_role')) }}</p>
                                </div>
                                <img class="w-10 h-10 rounded-full border-2 border-primary-fixed-dim bg-surface-dim object-cover"
                                     src="{{ session('profile_photo_url') ?? 'https://ui-avatars.com/api/?name=' . urlencode(session('user_name')) . '&background=032bbe&color=fff&size=80' }}"
                                     alt="Profile">
                                <span class="material-symbols-outlined text-on-surface-variant text-[20px]">expand_more</span>
                            </button>
                            <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-56 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg z-50 overflow-hidden">
                                <a href="{{ route('profile.edit') }}" class="flex items-center gap-md px-lg py-md text-body-sm font-body-sm text-on-surface hover:bg-surface-container-low transition-colors no-underline">
                                    <span class="material-symbols-outlined text-[20px]">person</span>
                                    Profil Saya
                                </a>
                                <a href="{{ route('settings') }}" class="flex items-center gap-md px-lg py-md text-body-sm font-body-sm text-on-surface hover:bg-surface-container-low transition-colors no-underline">
                                    <span class="material-symbols-outlined text-[20px]">settings</span>
                                    Pengaturan
                                </a>
                                <div class="border-t border-outline-variant"></div>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="flex items-center gap-md px-lg py-md text-body-sm font-body-sm text-error hover:bg-error-container/10 transition-colors w-full bg-transparent border-none cursor-pointer">
                                        <span class="material-symbols-outlined text-[20px]">logout</span>
                                        Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="p-lg max-w-container-max mx-auto w-full flex flex-col gap-lg">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Mobile Nav Bar -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 bg-surface border-t border-outline-variant px-md py-sm flex justify-around items-center z-50">
        <a class="flex flex-col items-center gap-xs {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-secondary' }}" href="{{ route('dashboard') }}">
            <span class="material-symbols-outlined">dashboard</span>
            <span class="text-[10px] font-semibold">Home</span>
        </a>
        @if(session('user_role') === 'admin')
            <a class="flex flex-col items-center gap-xs {{ request()->routeIs('users.*') ? 'text-primary' : 'text-secondary' }}" href="{{ route('users.index') }}">
                <span class="material-symbols-outlined">group</span>
                <span class="text-[10px]">Users</span>
            </a>
        @else
            <a class="flex flex-col items-center gap-xs {{ request()->routeIs('leave.index') ? 'text-primary' : 'text-secondary' }}" href="{{ route('leave.index') }}">
                <span class="material-symbols-outlined">event_note</span>
                <span class="text-[10px]">Leaves</span>
            </a>
        @endif
        <div class="relative -top-6">
            @if(session('user_role') === 'admin')
                <a href="{{ route('users.create') }}" class="w-14 h-14 bg-primary text-on-primary rounded-full shadow-lg shadow-primary/40 flex items-center justify-center">
                    <span class="material-symbols-outlined">person_add</span>
                </a>
            @else
                <a href="{{ route('leave.create') }}" class="w-14 h-14 bg-primary text-on-primary rounded-full shadow-lg shadow-primary/40 flex items-center justify-center">
                    <span class="material-symbols-outlined">add</span>
                </a>
            @endif
        </div>
        <a class="flex flex-col items-center gap-xs {{ request()->routeIs('reports.*') ? 'text-primary' : 'text-secondary' }}" href="{{ route('reports.index') }}">
            <span class="material-symbols-outlined">analytics</span>
            <span class="text-[10px]">Reports</span>
        </a>
        <a class="flex flex-col items-center gap-xs {{ request()->routeIs('settings') ? 'text-primary' : 'text-secondary' }}" href="{{ route('settings') }}">
            <span class="material-symbols-outlined">settings</span>
            <span class="text-[10px]">Settings</span>
        </a>
    </nav>

    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Fungsi buka/tutup menu navigasi di tampilan ponsel
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');

        if (mobileMenuBtn && sidebar) {
            mobileMenuBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('fixed');
            });
        }

        // Fungsi buka/tutup menu profil
        const profileDropdownBtn = document.getElementById('profileDropdownBtn');
        const profileDropdown = document.getElementById('profileDropdown');

        if (profileDropdownBtn && profileDropdown) {
            profileDropdownBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileDropdown.classList.toggle('hidden');
            });

            document.addEventListener('click', (e) => {
                if (!profileDropdownBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });
        }

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
 