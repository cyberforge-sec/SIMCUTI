<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>OAuth Login - SIMCUTI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .oauth-container {
            background: white;
            border-radius: 1.5rem;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 100%;
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #E5E7EB;
            border-top-color: #4F46E5;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 1.5rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .error-msg { color: #EF4444; display: none; margin-top: 1rem; }
        .success-icon { font-size: 3rem; color: #10B981; display: none; }
    </style>
</head>
<body>
    <div class="oauth-container">
        <div id="loading">
            <div class="spinner"></div>
            <h5>Sedang memproses login...</h5>
            <p class="text-muted">Mohon tunggu sebentar</p>
        </div>
        <div id="error" style="display:none;">
            <div style="font-size:3rem;color:#EF4444;">&#9888;</div>
            <h5 class="mt-2">Login Gagal</h5>
            <p class="error-msg" id="errorMsg" style="display:block;color:#6B7280;"></p>
            <a href="{{ route('login') }}" class="btn btn-primary mt-3">Kembali ke Login</a>
        </div>
    </div>

    <script>
        (function() {
            const hash = window.location.hash.substring(1);
            const params = new URLSearchParams(hash);
            const accessToken = params.get('access_token');
            const refreshToken = params.get('refresh_token');
            const state = params.get('state');
            const error = params.get('error');
            const errorDesc = params.get('error_description');

            console.log('OAuth callback - hash:', hash);
            console.log('OAuth callback - access_token:', accessToken ? 'present' : 'missing');
            console.log('OAuth callback - error:', error, errorDesc);

            // Also check query params (Supabase may send errors there)
            const queryParams = new URLSearchParams(window.location.search);
            const qError = queryParams.get('error');
            if (qError) {
                console.log('OAuth callback - query error:', qError, queryParams.get('error_description'));
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

            fetch('{{ route("oauth.handle") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    access_token: accessToken,
                    refresh_token: refreshToken,
                }),
            })
            .then(response => {
                console.log('OAuth handle response status:', response.status);
                return response.json().then(data => ({ ok: response.ok, data }));
            })
            .then(({ ok, data }) => {
                console.log('OAuth handle data:', data);
                if (ok && data.success) {
                    window.location.href = data.redirect;
                } else {
                    showError(data.error || 'Terjadi kesalahan saat login.');
                }
            })
            .catch(err => {
                console.error('OAuth handle fetch error:', err);
                showError('Gagal terhubung ke server. Periksa koneksi internet Anda.');
            });

            function showError(msg) {
                console.error('OAuth error:', msg);
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                document.getElementById('errorMsg').textContent = msg;
            }
        })();
    </script>
</body>
</html>
