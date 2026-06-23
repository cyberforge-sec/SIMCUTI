<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - SIMCUTI</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    <script>
        (function(){var w=console.warn;console.warn=function(){if(arguments.length&&typeof arguments[0]==='string'&&arguments[0].indexOf('cdn.tailwindcss.com')>-1)return;return w.apply(console,arguments);};})();
    </script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script>
        if (window.location.hash && window.location.hash.includes('access_token=')) {
            var hash = window.location.hash.substring(1);
            var hashParams = new URLSearchParams(hash);
            var accessToken = hashParams.get('access_token');
            var type = hashParams.get('type');
            
            if (accessToken) {
                if (type === 'recovery') {
                    // Show loading state
                    document.addEventListener("DOMContentLoaded", function() {
                        document.body.innerHTML = '<div style="display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;font-size:24px;">Memproses link reset password... Mohon tunggu.</div>';
                    });
                    window.location.href = '/reset-password?token=' + encodeURIComponent(accessToken) + '&type=recovery&is_access_token=1';
                } else if (type === 'signup') {
                    // For email verification signup, just remove the hash and alert them to login
                    window.location.hash = '';
                    document.addEventListener("DOMContentLoaded", function() {
                        Swal.fire({
                            icon: 'success',
                            title: 'Email Terverifikasi!',
                            text: 'Email Anda sudah berhasil diverifikasi. Silakan login sekarang.',
                            timer: 5000
                        });
                    });
                } else {
                    // Other token types, just clear the hash
                    window.location.hash = '';
                }
            }
        }
    </script>

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-background": "#131b2e",
                        "background": "#faf8ff",
                        "on-primary": "#ffffff",
                        "inverse-on-surface": "#eef0ff",
                        "surface-container-highest": "#dae2fd",
                        "surface-tint": "#344eda",
                        "secondary-fixed-dim": "#b7c8e1",
                        "on-surface": "#131b2e",
                        "on-primary-container": "#c9ceff",
                        "tertiary-fixed": "#e0e3e5",
                        "on-tertiary-container": "#cfd1d3",
                        "secondary-container": "#d0e1fb",
                        "on-error-container": "#93000a",
                        "on-primary-fixed": "#000f5c",
                        "on-error": "#ffffff",
                        "error-container": "#ffdad6",
                        "on-secondary": "#ffffff",
                        "on-secondary-container": "#54647a",
                        "tertiary-fixed-dim": "#c4c7c9",
                        "tertiary": "#3f4345",
                        "on-tertiary": "#ffffff",
                        "surface-bright": "#faf8ff",
                        "surface-dim": "#d2d9f4",
                        "primary-container": "#2e49d5",
                        "outline": "#757686",
                        "inverse-surface": "#283044",
                        "surface-container-lowest": "#ffffff",
                        "primary-fixed": "#dee0ff",
                        "primary": "#032bbe",
                        "inverse-primary": "#bac3ff",
                        "on-tertiary-fixed": "#191c1e",
                        "error": "#ba1a1a",
                        "on-tertiary-fixed-variant": "#444749",
                        "secondary": "#505f76",
                        "on-surface-variant": "#444655",
                        "surface": "#faf8ff",
                        "secondary-fixed": "#d3e4fe",
                        "on-secondary-fixed-variant": "#38485d",
                        "outline-variant": "#c5c5d7",
                        "surface-container-high": "#e2e7ff",
                        "on-secondary-fixed": "#0b1c30",
                        "surface-variant": "#dae2fd",
                        "primary-fixed-dim": "#bac3ff",
                        "surface-container-low": "#f2f3ff",
                        "on-primary-fixed-variant": "#0f31c2",
                        "tertiary-container": "#575a5c",
                        "surface-container": "#eaedff"
                    },
                    borderRadius: {
                        DEFAULT: "0.25rem",
                        lg: "0.5rem",
                        xl: "0.75rem",
                        full: "9999px"
                    },
                    spacing: {
                        lg: "24px",
                        "container-max": "1280px",
                        md: "16px",
                        unit: "4px",
                        xxl: "48px",
                        xl: "32px",
                        xs: "4px",
                        sm: "8px",
                        gutter: "24px"
                    },
                    fontFamily: {
                        "label-sm": ["Plus Jakarta Sans"],
                        "body-lg": ["Plus Jakarta Sans"],
                        "headline-lg-mobile": ["Plus Jakarta Sans"],
                        "headline-xl": ["Plus Jakarta Sans"],
                        "body-sm": ["Plus Jakarta Sans"],
                        "label-md": ["Plus Jakarta Sans"],
                        "headline-md": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"]
                    },
                    fontSize: {
                        "label-sm": ["12px", { lineHeight: "16px", fontWeight: "500" }],
                        "body-lg": ["18px", { lineHeight: "28px", fontWeight: "400" }],
                        "headline-lg-mobile": ["24px", { lineHeight: "32px", fontWeight: "700" }],
                        "headline-xl": ["40px", { lineHeight: "48px", letterSpacing: "-0.02em", fontWeight: "700" }],
                        "body-sm": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                        "label-md": ["14px", { lineHeight: "20px", letterSpacing: "0.01em", fontWeight: "600" }],
                        "headline-md": ["24px", { lineHeight: "32px", fontWeight: "600" }],
                        "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "headline-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }]
                    }
                }
            }
        };
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-card {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow:
                0 10px 25px -5px rgba(0, 0, 0, 0.1),
                0 20px 48px -12px rgba(0, 0, 0, 0.2);
        }
        .deep-shadow {
            box-shadow:
                0px 4px 6px -1px rgba(0, 0, 0, 0.2),
                0px 10px 15px -3px rgba(0, 0, 0, 0.2),
                0px 20px 25px -5px rgba(0, 0, 0, 0.15);
        }
        .mesh-gradient-bg {
            background-color: #faf8ff;
            background-image:
                radial-gradient(at 0% 0%, rgba(3, 43, 190, 0.03) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(3, 43, 190, 0.05) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(208, 225, 251, 0.2) 0px, transparent 50%);
        }
        .abstract-circle {
            position: absolute;
            border-radius: 50%;
            filter: blur(80px);
            z-index: 0;
            pointer-events: none;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
        .btn-gradient {
            background: linear-gradient(135deg, #032bbe 0%, #344eda 100%);
        }
        .calendar-container {
            width: 100%;
            max-width: 440px;
            overflow: hidden;
            border-radius: 2rem;
            position: relative;
        }
        .calendar-track {
            display: flex;
            width: 100%;
            transition: transform 0.8s cubic-bezier(0.65, 0, 0.35, 1);
            will-change: transform;
        }
        .calendar-month-slide {
            flex: 0 0 100%;
            padding: 2rem;
            box-sizing: border-box;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .swal2-popup {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }
    </style>

    @stack('styles')
</head>
<body class="text-on-background min-h-screen flex flex-col overflow-x-hidden relative mesh-gradient-bg">
    <!-- Background Accents -->
    <div class="abstract-circle w-[600px] h-[600px] bg-primary/5 top-[-10%] left-[-5%]"></div>
    <div class="abstract-circle w-[400px] h-[400px] bg-secondary-container/20 bottom-[10%] right-[-5%]"></div>

    <main class="flex-grow flex flex-col md:flex-row w-full h-full min-h-screen relative z-10">
        <!-- Left Panel -->
        <section class="hidden md:flex flex-col flex-[1.3] relative items-center justify-center p-xxl overflow-hidden bg-gradient-to-br from-[#032bbe] via-[#2e49d5] to-[#14b8a6] shadow-2xl" style="border-radius: 0 80px 80px 0; z-index: 2;">
            <div class="absolute inset-0 z-0 opacity-20">
                <div class="absolute top-[-10%] right-[-10%] w-[600px] h-[600px] bg-white rounded-full blur-[150px] opacity-20"></div>
                <div class="absolute bottom-[-10%] left-[-10%] w-[400px] h-[400px] bg-blue-400 rounded-full blur-[120px] opacity-30"></div>
            </div>

            <div class="relative z-10 w-full max-w-2xl flex flex-col items-center justify-center min-h-[80vh] space-y-xxl">
                <div class="text-center space-y-md px-lg">
                    <h1 class="font-headline-xl text-[44px] leading-tight text-white tracking-tight drop-shadow-xl font-extrabold max-w-lg mx-auto">
                        Sistem Informasi Manajemen Cuti
                    </h1>
                </div>

                <div class="calendar-container glass-card deep-shadow">
                    <div class="calendar-track" id="calendar-track" style="transform: translateX(-200%);"></div>
                </div>

                <div class="text-center max-w-lg px-lg">
                    <p class="font-body-lg text-lg text-white/95 leading-relaxed drop-shadow-sm font-medium">Kelola pengajuan cuti, jadwal kerja, dan produktivitas tim dalam satu platform modern.</p>
                </div>
            </div>

            <div class="absolute bottom-12 left-12 w-48 h-[2px] bg-gradient-to-r from-white/30 to-transparent"></div>
        </section>

        <!-- Right Panel -->
        <section class="flex-1 flex items-center justify-center p-md md:p-xxl bg-background/40">
            <div class="w-full max-w-md backdrop-blur-md bg-white/70 border border-white/50 p-xl md:p-xxl rounded-[2.5rem] space-y-lg shadow-xl">
                @yield('content')
            </div>
        </section>
    </main>

    <footer class="flex justify-center items-center w-full px-lg py-md mt-auto bg-transparent relative z-10">
        <div class="font-label-sm text-label-sm text-on-surface-variant/60">&copy; 2026 SIMCUTI from Adiva</div>
    </footer>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Password toggle
        function togglePassword(inputId, btnEl) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            if (btnEl) {
                btnEl.textContent = type === 'password' ? 'visibility' : 'visibility_off';
            }
        }

        // Captcha refresh
        function refreshCaptcha() {
            fetch('/captcha/refresh', {
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const img = document.getElementById('captchaImage');
                    if (img) img.src = data.captcha;
                }
            });
        }

        // Calendar Slider
        const months = [
            { name: "Januari", days: 31, startDay: 4 },
            { name: "Februari", days: 28, startDay: 0 },
            { name: "Maret", days: 31, startDay: 0 },
            { name: "April", days: 30, startDay: 3 }
        ];

        const track = document.getElementById('calendar-track');
        if (track) {
            function createCalendarSlide(monthData) {
                const slide = document.createElement('div');
                slide.className = 'calendar-month-slide';
                let html = `
                    <div class="flex justify-between items-center mb-8">
                        <span class="text-2xl font-bold text-white tracking-tight">${monthData.name}</span>
                        <div class="flex gap-4">
                            <span class="material-symbols-outlined text-white/40 text-xl cursor-pointer hover:text-white/80 transition-colors">chevron_left</span>
                            <span class="material-symbols-outlined text-white/80 text-xl cursor-pointer hover:text-white transition-colors">chevron_right</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-7 gap-y-4 gap-x-2 text-center">
                        ${['Sen','Sel','Rab','Kam','Jum','Sab','Min'].map(d =>
                            '<div class="text-[11px] uppercase font-bold text-white/30 pb-2">' + d + '</div>'
                        ).join('')}
                `;
                for (let i = 0; i < monthData.startDay; i++) {
                    html += '<div class="h-10"></div>';
                }
                for (let day = 1; day <= monthData.days; day++) {
                    const isToday = day === 12;
                    const isCuti = [15, 22].includes(day);
                    const cellClass = isToday
                        ? 'bg-white text-primary font-bold rounded-xl shadow-xl'
                        : isCuti
                            ? 'border border-white/30 text-white font-semibold rounded-xl bg-white/5'
                            : 'text-white/80 hover:bg-white/10 rounded-xl transition-colors';
                    html += '<div class="h-10 flex items-center justify-center text-sm ' + cellClass + '">' + day + '</div>';
                }
                html += '</div>';
                slide.innerHTML = html;
                return slide;
            }

            months.forEach(m => track.appendChild(createCalendarSlide(m)));

            let currentIndex = 0;
            setInterval(() => {
                currentIndex = (currentIndex + 1) % months.length;
                track.style.transform = 'translateX(-' + (currentIndex * 100) + '%)';
            }, 5000);
        }

        // SweetAlert2 toasts
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

        @if($errors->any())
            Swal.fire({
                icon: 'error',
                title: 'Terjadi Kesalahan',
                html: '<ul style="text-align: left;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>'
            });
        @endif
    </script>

    @stack('scripts')
</body>
</html>
