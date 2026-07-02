{{-- Tampilan antarmuka (UI) halaman oauth-callback. --}}
<!DOCTYPE html>
<html class="light" lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <title>OAuth Login - SIMCUTI</title>

    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "on-background": "#131b2e",
                        "background": "#faf8ff",
                        "on-primary": "#ffffff",
                        "on-surface": "#131b2e",
                        "on-surface-variant": "#444655",
                        "primary": "#032bbe",
                        "primary-container": "#2e49d5",
                        "secondary-container": "#d0e1fb",
                        "error": "#ba1a1a",
                        "outline": "#757686",
                        "outline-variant": "#c5c5d7",
                        "surface": "#faf8ff",
                    },
                    spacing: {
                        lg: "24px",
                        md: "16px",
                        xxl: "48px",
                        xl: "32px",
                        sm: "8px",
                    },
                    fontFamily: {
                        "label-md": ["Plus Jakarta Sans"],
                        "body-md": ["Plus Jakarta Sans"],
                        "body-sm": ["Plus Jakarta Sans"],
                        "headline-lg": ["Plus Jakarta Sans"],
                    },
                    fontSize: {
                        "label-md": ["14px", { lineHeight: "20px", letterSpacing: "0.01em", fontWeight: "600" }],
                        "body-md": ["16px", { lineHeight: "24px", fontWeight: "400" }],
                        "body-sm": ["14px", { lineHeight: "20px", fontWeight: "400" }],
                        "headline-lg": ["32px", { lineHeight: "40px", letterSpacing: "-0.02em", fontWeight: "700" }],
                    }
                }
            }
        };
    </script>

    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }

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

        .oauth-spinner {
            width: 56px;
            height: 56px;
            border: 4px solid #c5c5d7;
            border-top-color: #032bbe;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 24px;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(3, 43, 190, 0.2); }
            50% { box-shadow: 0 0 0 12px rgba(3, 43, 190, 0); }
        }

        .pulse-ring {
            animation: pulse-glow 2s ease-in-out infinite;
        }
    </style>
</head>
<body class="text-on-background min-h-screen flex items-center justify-center overflow-x-hidden relative mesh-gradient-bg">
    <!-- Background Accents -->
    <div class="abstract-circle w-[600px] h-[600px] bg-primary/5 top-[-10%] left-[-5%]"></div>
    <div class="abstract-circle w-[400px] h-[400px] bg-secondary-container/20 bottom-[10%] right-[-5%]"></div>

    <!-- Main Card -->
    <div class="relative z-10 w-full max-w-md mx-4 backdrop-blur-md bg-white/70 border border-white/50 p-xl md:p-xxl rounded-[2.5rem] shadow-xl text-center">

        <!-- Loading State -->
        <div id="loading">
            <div class="w-20 h-20 mx-auto mb-lg bg-primary/10 rounded-full flex items-center justify-center pulse-ring">
                <div class="oauth-spinner" style="margin: 0;"></div>
            </div>
            <h2 class="font-headline-lg text-[22px] font-bold text-on-background mb-sm">Sedang Memproses Login...</h2>
            <p class="font-body-md text-body-md text-on-surface-variant" id="statusMsg">Memverifikasi token OAuth</p>
            <p class="font-body-sm text-body-sm text-outline mt-sm" id="timerMsg"></p>
        </div>

        <!-- Error State -->
        <div id="error" style="display: none;">
            <div class="w-20 h-20 mx-auto mb-lg bg-error/10 rounded-full flex items-center justify-center">
                <span class="material-symbols-outlined text-error" style="font-size: 40px;">error</span>
            </div>
            <h2 class="font-headline-lg text-[22px] font-bold text-on-background mb-sm">Login Gagal</h2>
            <p class="font-body-md text-body-md text-on-surface-variant mb-lg" id="errorMsg"></p>
            <div class="flex flex-col gap-md">
                <a href="{{ route('login') }}" class="btn-gradient w-full py-md text-on-primary rounded-xl font-label-md text-label-md shadow-lg shadow-primary/20 hover:shadow-xl hover:shadow-primary/30 hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300 text-center">
                    Kembali ke Login
                </a>
                <button onclick="location.reload()" class="w-full py-md bg-white border border-outline-variant/50 rounded-xl font-label-md text-label-md text-on-surface hover:bg-primary/5 hover:border-primary/30 active:scale-95 transition-all duration-200">
                    Coba Lagi
                </button>
            </div>
        </div>
    </div>

    <script>
        (function() {
            const statusMsg = document.getElementById('statusMsg');
            const timerMsg = document.getElementById('timerMsg');
            const TIMEOUT_MS = 15000;

            // LANGKAH 1: Ambil token keamanan langsung dari URL
            const hash = window.location.hash.substring(1);
            const params = new URLSearchParams(hash);
            const accessToken = params.get('access_token');
            const refreshToken = params.get('refresh_token');
            const error = params.get('error');
            const errorDesc = params.get('error_description');

            // LANGKAH 2: Bersihkan sisa URL demi keamanan
            try {
                window.history.replaceState(null, '', window.location.pathname + window.location.search);
            } catch(e) {
                window.location.hash = '';
            }

            // Periksa apakah terdapat error pada jaringan
            const queryParams = new URLSearchParams(window.location.search);
            const qError = queryParams.get('error');
            if (qError) {
                showError(queryParams.get('error_description') || qError);
                return;
            }

            if (error) {
                showError(errorDesc || error);
                return;
            }

            if (!accessToken || !refreshToken) {
                showError('Token tidak ditemukan. Pastikan Anda mengakses halaman ini melalui proses OAuth.');
                return;
            }

            // LANGKAH 3: Hitung mundur sebelum dialihkan otomatis
            let elapsed = 0;
            const timerInterval = setInterval(function() {
                elapsed++;
                timerMsg.textContent = elapsed + ' detik...';
                if (elapsed === 3) statusMsg.textContent = 'Membuat sesi login';
                if (elapsed === 7) statusMsg.textContent = 'Menyiapkan verifikasi 2FA';
                if (elapsed === 12) statusMsg.textContent = 'Hampir selesai, mohon tunggu';
            }, 1000);

            // LANGKAH 4: Ambil data dari server dengan batasan waktu
            const controller = new AbortController();
            const timeoutId = setTimeout(function() { controller.abort(); }, TIMEOUT_MS);

            fetch('{{ route("oauth.handle") }}', {
                method: 'POST',
                signal: controller.signal,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                referrerPolicy: 'no-referrer',
                body: JSON.stringify({
                    access_token: accessToken,
                    refresh_token: refreshToken,
                }),
            })
            .then(function(response) {
                clearTimeout(timeoutId);
                clearInterval(timerInterval);
                return response.json().then(function(data) { return { ok: response.ok, data: data }; });
            })
            .then(function(result) {
                if (result.ok && result.data.success) {
                    statusMsg.textContent = 'Login berhasil! Mengalihkan...';
                    timerMsg.textContent = '';
                    window.location.replace(result.data.redirect);
                } else {
                    showError(result.data.error || 'Terjadi kesalahan saat login.');
                }
            })
            .catch(function(err) {
                clearTimeout(timeoutId);
                clearInterval(timerInterval);
                if (err.name === 'AbortError') {
                    showError('Server tidak merespons dalam ' + (TIMEOUT_MS/1000) + ' detik. Silakan coba lagi.');
                } else {
                    showError('Gagal terhubung ke server. Periksa koneksi internet Anda.');
                }
            });

            function showError(msg) {
                clearInterval(timerInterval);
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                document.getElementById('errorMsg').textContent = msg;
            }
        })();
    </script>
</body>
</html>
   