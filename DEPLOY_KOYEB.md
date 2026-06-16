# Panduan Deploy SIMCUTI ke Koyeb

## Persiapan

### 1. Install Koyeb CLI
```bash
# Linux
curl -sL https://github.com/koyeb/koyeb-cli/releases/latest/download/koyeb_linux_amd64.tar.gz | tar xz
sudo mv koyeb /usr/local/bin/

# Verifikasi
koyeb version
```

### 2. Login ke Koyeb
```bash
koyeb login
```

### 3. Generate APP_KEY (jika belum ada)
```bash
php artisan key:generate --show
```

### 4. Set Environment Variables di Koyeb Dashboard

Login ke https://app.koyeb.com dan tambahkan environment variables berikut di Settings > Environment:

**Required:**
- `APP_KEY` = base64:key_yang_sudah_di_generate
- `SUPABASE_URL` = https://your-project.supabase.co
- `SUPABASE_ANON_KEY` = your-anon-key
- `SUPABASE_SERVICE_KEY` = your-service-role-key

**Email (untuk 2FA & Reset Password):**
- `MAIL_HOST` = smtp.gmail.com (atau SMTP lain)
- `MAIL_USERNAME` = your-email@gmail.com
- `MAIL_PASSWORD` = your-app-password
- `MAIL_FROM_ADDRESS` = noreply@simcuti.com

## Deploy

### Option 1: Deploy dengan Koyeb CLI (Recommended)

```bash
# Dari root project SIMCUTI
koyeb services create simcuti \
  --docker ./Dockerfile \
  --port 8000:http \
  --env APP_NAME=SIMCUTI \
  --env APP_ENV=production \
  --env APP_DEBUG=false \
  --env APP_TIMEZONE=Asia/Jakarta \
  --env SUPABASE_STORAGE_BUCKET=leave-attachments \
  --env MAIL_MAILER=smtp \
  --env MAIL_PORT=587 \
  --env MAIL_ENCRYPTION=tls \
  --env MAIL_FROM_NAME=SIMCUTI
```

### Option 2: Deploy dengan Git (Otomatis)

1. Push code ke GitHub/GitLab
2. Di Koyeb Dashboard:
   - Create new service
   - Connect GitHub repository
   - Select branch (main/master)
   - Build type: Docker
   - Dockerfile location: `./Dockerfile`
   - Port: 8000
   - Add environment variables

### Option 3: Deploy dengan koyeb.yaml

```bash
koyeb apps init
# atau
koyeb apps deploy
```

## Verifikasi

1. Cek status deployment:
   ```bash
   koyeb services list
   koyeb instances list
   ```

2. Cek logs:
   ```bash
   koyeb instances logs <instance-id>
   ```

3. Akses aplikasi:
   ```
   https://your-app-name.koyeb.app
   ```

## Troubleshooting

### Build Failed
```bash
# Cek build logs
koyeb instances logs <instance-id> --type build
```

### Runtime Error
```bash
# Cek runtime logs
koyeb instances logs <instance-id> --type runtime
```

### Health Check Failed
- Pastikan port 8000 terbuka
- Cek nginx dan PHP-FPM berjalan
- Verifikasi environment variables

## Update Deployment

Setelah ada perubahan code:

```bash
# Jika pakai Git (otomatis deploy saat push)
git push origin main

# Jika manual
koyeb services redeploy simcuti
```

## Monitoring

```bash
# Lihat metrics
koyeb metrics get simcuti

# Lihat semua instances
koyeb instances list

# Scale service (jika perlu)
koyeb services update simcuti --scale-min 2 --scale-max 5
```

## Notes

- Koyeb free tier: 1 nano instance (256MB RAM)
- Auto-deploy dari Git tersedia
- HTTPS otomatis (Let's Encrypt)
- Custom domain bisa ditambahkan di Settings > Domains
- Database tetap di Supabase (tidak perlu deploy terpisah)
