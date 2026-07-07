# 🚀 Setup Guide — SIMCUTI

Panduan instalasi dan konfigurasi **Sistem Informasi Manajemen Cuti (SIMCUTI)**.

---

## 📋 Prerequisites

| Software | Version | Link |
|---|---|---|
| PHP | ^8.2 | [php.net](https://php.net) |
| Composer | latest | [getcomposer.org](https://getcomposer.org) |
| Node.js | ^18 / ^20 | [nodejs.org](https://nodejs.org) |
| Supabase Account | — | [supabase.com](https://supabase.com) |
| Docker (opsional) | latest | [docker.com](https://docker.com) |

---

## 🚀 Quick Start

### 1. Clone Repository

```bash
git clone git@github.com:cyberforge-sec/simcuti.git
cd simcuti
```

### 2. Install Dependencies

```bash
composer install
npm install
npm run build
```

### 3. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Setup Supabase

Buat project di [Supabase Dashboard](https://supabase.com/dashboard), lalu copy konfigurasi ke `.env`:

```dotenv
SUPABASE_URL=https://your-project.supabase.co
SUPABASE_ANON_KEY=your_anon_key_here
SUPABASE_SERVICE_KEY=your_service_role_key_here
SUPABASE_STORAGE_BUCKET=leave-attachments
```

**Cara dapetin credential:**
1. Buka Supabase Dashboard → **Settings** → **API**
2. `SUPABASE_URL` → **Project URL**
3. `SUPABASE_ANON_KEY` → **anon public key**
4. `SUPABASE_SERVICE_KEY` → **service_role key** (ada di bawah, klik **Reveal**)

### 5. Database Migration

Buka **Supabase Dashboard → SQL Editor**, jalankan file SQL berikut:

1. `database/fix_rls_recursion.sql`
2. `database/fix_ctg_triggers.sql`

Atau langsung paste:

```bash
# Atau via psql (butuh connection string dari Supabase)
# psql "$SUPABASE_DB_URL" -f database/fix_rls_recursion.sql
# psql "$SUPABASE_DB_URL" -f database/fix_ctg_triggers.sql
```

### 6. Buat Storage Bucket

Di Supabase Dashboard:
1. **Storage** → **Create bucket**
2. Name: `leave-attachments` (atau sesuai `SUPABASE_STORAGE_BUCKET`)
3. **Public bucket** (sesuai kebutuhan)

### 7. Jalankan Aplikasi

```bash
# Development server
composer run dev
```

Akses di: **http://localhost:8080**

---

## 🐳 Docker Setup (Alternatif)

```bash
# Build & start containers
docker compose up -d --build

# Install dependencies di dalam container
docker compose exec php composer install
docker compose exec php npm install
docker compose exec php npm run build

# Setup environment
cp .env.example .env
docker compose exec php php artisan key:generate
```

Akses di: **http://localhost**

---

## 🔧 Environment Variables

| Variable | Description | Default |
|---|---|---|
| `APP_NAME` | Nama aplikasi | SIMCUTI |
| `APP_ENV` | Environment (`local`, `production`) | local |
| `APP_DEBUG` | Debug mode | true |
| `APP_URL` | Base URL aplikasi | http://localhost:8080 |
| `APP_TIMEZONE` | Zona waktu | Asia/Jakarta |
| `SUPABASE_URL` | Supabase project URL | required |
| `SUPABASE_ANON_KEY` | Supabase anonymous key | required |
| `SUPABASE_SERVICE_KEY` | Supabase service role key | required |
| `SUPABASE_STORAGE_BUCKET` | Storage bucket name | leave-attachments |
| `SESSION_DRIVER` | Session storage (`file`, `database`) | file |
| `SESSION_LIFETIME` | Session lifetime (menit) | 120 |
| `MAIL_MAILER` | Mail driver (`log`, `smtp`) | log |

---

## 🧪 Akun Test

Setelah seeding database, akun default:

| Role | Email | Password |
|---|---|---|
| Admin | admin@simcuti.test | (cek seed) |
| Manager | manager@simcuti.test | (cek seed) |
| Karyawan | karyawan@simcuti.test | (cek seed) |

---

## 📁 Struktur Database (Supabase)

### Tables

| Table | Description |
|---|---|
| `profiles` | Data pengguna (nama, department, role, avatar) |
| `leave_requests` | Pengajuan cuti |
| `leave_types` | Jenis cuti (tahunan, sakit, dll) |
| `departments` | Departemen/perusahaan |
| `two_factor_codes` | Kode verifikasi 2FA |
| `activity_logs` | Log aktivitas pengguna |

---
