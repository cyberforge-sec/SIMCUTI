# SIMCUTI - Sistem Informasi Manajemen Cuti

Aplikasi web untuk mengelola pengajuan cuti karyawan dengan sistem approval multi-level. Dibangun menggunakan **Laravel 12** sebagai frontend framework dan **Supabase** sebagai backend (PostgreSQL + Auth + Storage).

## Fitur

- **Multi-role**: Admin, Manager, Karyawan dengan hak akses berbeda
- **Autentikasi**: Email/password, OAuth (Google & GitHub), 2FA
- **Pengajuan Cuti**: Buat, edit, batalkan, dengan deteksi tanggal tumpang tindih
- **Approval Workflow**: Manager/Admin menyetujui atau menolak pengajuan
- **Dashboard**: Statistik dan grafik per role (admin, manager, karyawan)
- **Laporan**: Export ke CSV, Excel, dan PDF
- **Notifikasi**: Real-time notification badge
- **Activity Log**: Pencatatan semua aktivitas pengguna
- **Captcha & Rate Limiting**: Proteksi dari brute-force dan bot

## Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Framework | Laravel 12 (PHP 8.2+) |
| Database | Supabase PostgreSQL |
| Auth | Supabase Auth (JWT) |
| Storage | Supabase Storage |
| Frontend | Tailwind CSS, Alpine.js, Chart.js |
| Build | Vite |

## Instalasi

```bash
# Clone repository
git clone <repo-url> SIMCUTI
cd SIMCUTI

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Konfigurasi .env dengan credentials Supabase Anda
# SUPABASE_URL, SUPABASE_ANON_KEY, SUPABASE_SERVICE_KEY, dll.

# Jalankan database migration di Supabase SQL Editor
# (lihat file database/supabase.sql)

# Build asset
npm run build

# Jalankan aplikasi
composer run dev
```

## Struktur Proyek

```
app/
├── Http/
│   ├── Controllers/    # Request handling per fitur
│   └── Middleware/     # Auth, role, 2FA checks
├── Services/
│   ├── SupabaseService.php    # Wrapper untuk Supabase API
│   ├── ActivityLogService.php # Pencatatan aktivitas
│   └── CaptchaService.php     # Custom captcha generator
routes/
└── web.php             # Semua route aplikasi
resources/
├── views/              # Blade templates
├── css/                # Tailwind CSS
└── js/                 # JavaScript
```

## Role & Hak Akses

| Fitur | Karyawan | Manager | Admin |
|-------|----------|---------|-------|
| Ajukan cuti | Ya | Ya | Ya |
| Lihat pengajuan sendiri | Ya | Ya | Ya |
| Approve/reject tim | - | Ya | Ya |
| Kelola pengguna | - | - | Ya |
| Kelola departemen & jenis cuti | - | - | Ya |
| Lihat laporan | - | Ya | Ya |
| Activity logs | - | - | Ya |

## 📄 License

MIT — see [LICENSE](LICENSE) for details.
 
 
 
 
