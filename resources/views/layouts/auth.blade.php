<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Login') - SIMCUTI</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            overflow: hidden;
        }

        .auth-container {
            width: 100%;
            max-width: 1000px;
            max-height: calc(100vh - 3rem);
            background: white;
            border-radius: 1.5rem;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
        }

        .auth-left {
            flex: 0.7;
            background: linear-gradient(180deg, #1E293B 0%, #0F172A 100%);
            padding: 2rem 2rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 2.5rem;
            position: relative;
            overflow: hidden;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(79, 70, 229, 0.1);
            border-radius: 50%;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 300px;
            height: 300px;
            background: rgba(129, 140, 248, 0.1);
            border-radius: 50%;
        }

        .auth-logo {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }

        .auth-logo i {
            font-size: 2.5rem;
            color: var(--color-primary-light, #818CF8);
        }

        .auth-logo-text {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -1px;
        }

        .auth-left h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 1;
        }

        .auth-left p {
            color: #CBD5E1;
            font-size: 0.9375rem;
            line-height: 1.5;
            position: relative;
            z-index: 1;
        }

        .auth-features {
            list-style: none;
            margin-top: 1.5rem;
            position: relative;
            z-index: 1;
            padding: 0;
        }

        .auth-features li {
            color: #CBD5E1;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.875rem;
        }

        .auth-features i {
            color: var(--color-primary-light, #818CF8);
            font-size: 1.25rem;
        }

        .auth-right {
            flex: 1.4;
            padding: 2rem 2.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
        }

        .auth-header {
            margin-bottom: 1.25rem;
            text-align: center;
        }

        .auth-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1F2937;
            margin-bottom: 0.25rem;
        }

        .auth-subtitle {
            color: #6B7280;
            font-size: 1rem;
        }

        .auth-footer {
            margin-top: 1rem;
            text-align: center;
            color: #6B7280;
            font-size: 0.9375rem;
        }

        .auth-footer a {
            color: var(--color-primary, #4F46E5);
            text-decoration: none;
            font-weight: 500;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1rem 0;
            color: #9CA3AF;
            font-size: 0.875rem;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #E5E7EB;
        }

        .divider span {
            padding: 0 1rem;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            font-size: 1rem;
            transition: opacity 0.2s ease;
            pointer-events: none;
        }

        .input-group:focus-within .input-icon {
            opacity: 0;
        }

        .input-group .form-control {
            padding-left: 2.5rem;
            transition: padding-left 0.2s ease;
        }

        .input-group:focus-within .form-control {
            padding-left: 1rem;
        }

        .input-group-append {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .btn-toggle-password {
            background: transparent;
            border: none;
            color: #9CA3AF;
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
        }

        .btn-toggle-password:hover {
            color: var(--color-primary, #4F46E5);
        }

        .captcha-row {
            display: flex;
            gap: 0.75rem;
            align-items: stretch;
        }

        .captcha-row .captcha-left {
            flex: 1.5;
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .captcha-row .captcha-right {
            flex: 1;
            display: flex;
            align-items: center;
        }

        .captcha-row .captcha-right .form-control {
            height: 45px;
            font-size: 0.875rem;
        }

        .captcha-container {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex: 1;
        }

        .captcha-image {
            flex: 1;
            height: 45px;
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            border-radius: 0.375rem;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .captcha-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-refresh-captcha {
            width: 45px;
            height: 45px;
            min-width: 45px;
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 0.375rem;
            color: var(--color-primary, #4F46E5);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-refresh-captcha:hover {
            background: var(--color-primary, #4F46E5);
            color: white;
            border-color: var(--color-primary, #4F46E5);
        }

        .form-group {
            margin-bottom: 0.625rem;
        }

        .form-group .form-label {
            font-size: 0.8125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #374151;
        }

        .form-group .form-control {
            padding-top: 0.375rem;
            padding-bottom: 0.375rem;
            font-size: 0.875rem;
            border-radius: 0.375rem;
        }

        .form-row {
            display: flex;
            gap: 0.75rem;
        }

        .form-row .form-group {
            flex: 1;
        }

        .form-row .btn-oauth {
            flex: 1;
            margin-bottom: 0;
        }

        .btn-oauth {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #E5E7EB;
            border-radius: 0.375rem;
            font-weight: 500;
            font-size: 0.8125rem;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0.4rem;
        }

        .btn-oauth:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-oauth-github {
            background: #24292e;
            color: white;
            border-color: #24292e;
        }

        .btn-oauth-github:hover {
            background: #1b1f23;
            color: white;
        }

        .btn-oauth-google {
            background: white;
            color: #374151;
        }

        .btn-oauth-google:hover {
            background: #F9FAFB;
            color: #374151;
        }

        .auth-btn-primary {
            width: 100%;
            padding: 0.5rem;
            background: var(--color-primary, #4F46E5);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .auth-btn-primary:hover {
            background: var(--color-primary-dark, #4338CA);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        }

        .auth-btn-primary:disabled {
            transform: none;
            box-shadow: none;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .form-check {
            display: flex;
            align-items: center;
        }

        .form-check-input {
            width: 1rem;
            height: 1rem;
            margin: 0;
            cursor: pointer;
            flex-shrink: 0;
        }

        .form-check-label {
            cursor: pointer;
            margin-left: 0.5rem;
        }

        @media (max-width: 768px) {
            body {
                padding: 0.75rem;
                height: 100vh;
                overflow: hidden;
            }

            .auth-container {
                flex-direction: column;
                max-height: calc(100vh - 1.5rem);
            }

            .auth-left {
                padding: 1.5rem;
                display: none;
            }

            .auth-right {
                padding: 1.5rem;
                overflow-y: auto;
            }

            .auth-left h2 {
                font-size: 1.5rem;
            }
        }
    </style>

    @stack('styles')
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <div class="auth-logo">
                <i class="bi bi-calendar-check-fill"></i>
                <span class="auth-logo-text">SIMCUTI</span>
            </div>

            <h2>Sistem Informasi Manajemen Cuti</h2>
            <p>Kelola pengajuan cuti karyawan dengan mudah, cepat, dan aman.</p>
        </div>

        <div class="auth-right">
            @yield('content')
        </div>
    </div>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        function refreshCaptcha() {
            const captchaImg = document.getElementById('captchaImage');
            if (captchaImg) {
                captchaImg.src = '/captcha?' + new Date().getTime();
            }
        }

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
