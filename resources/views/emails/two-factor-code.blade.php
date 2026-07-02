{{-- Tampilan antarmuka (UI) halaman two-factor-code. --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f3f4f6;">
    <table width="100%" cellpadding="0" cellspacing="0" style="padding: 2rem;">
        <tr>
            <td align="center">
                <table width="480" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: #4F46E5; padding: 1.5rem; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 1.5rem;">SIMCUTI</h1>
                            <p style="color: #c7d2fe; margin: 0.25rem 0 0; font-size: 0.875rem;">Sistem Informasi Manajemen Cuti</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 2rem;">
                            <p style="margin: 0 0 1rem; color: #374151; font-size: 1rem;">
                                Halo <strong>{{ $userName }}</strong>,
                            </p>
                            <p style="margin: 0 0 1.5rem; color: #6b7280; font-size: 0.9375rem;">
                                Gunakan kode berikut untuk verifikasi login Anda:
                            </p>
                            <div style="background: #f0f0ff; border: 2px dashed #4F46E5; border-radius: 8px; padding: 1.5rem; text-align: center; margin-bottom: 1.5rem;">
                                <span style="font-size: 2.5rem; font-weight: 700; letter-spacing: 0.75rem; color: #4F46E5; font-family: monospace;">{{ $code }}</span>
                            </div>
                            <p style="margin: 0 0 0.5rem; color: #6b7280; font-size: 0.8125rem;">
                                Kode ini berlaku selama <strong>10 menit</strong>. Jangan bagikan kode ini kepada siapapun.
                            </p>
                            <p style="margin: 0; color: #9ca3af; font-size: 0.8125rem;">
                                Jika Anda tidak merasa melakukan login, abaikan email ini dan segera ganti password Anda.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #f9fafb; padding: 1rem; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #9ca3af; font-size: 0.75rem;">
                                &copy; {{ date('Y') }} SIMCUTI. Email ini dikirim secara otomatis.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
   