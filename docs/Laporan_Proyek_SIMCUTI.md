# 2. Laporan Proyek

# SIMCUTI - Sistem Informasi Manajemen Cuti

## Bab 1 Pendahuluan

### 1.1 Latar Belakang

Pengelolaan cuti karyawan secara manual sering menimbulkan masalah seperti data pengajuan yang tercecer, proses persetujuan yang lambat, sulitnya memantau sisa cuti, serta kurangnya jejak audit terhadap aktivitas pengguna. Dalam organisasi yang memiliki banyak departemen dan tingkatan jabatan, proses cuti membutuhkan alur yang jelas agar karyawan, manager, dan admin dapat menjalankan perannya masing-masing.

SIMCUTI dibuat sebagai aplikasi web untuk membantu proses manajemen cuti karyawan secara terpusat. Aplikasi ini mendukung pengajuan cuti, persetujuan oleh manager atau admin, pengelolaan data master, laporan, notifikasi, dan pencatatan aktivitas. Sistem dibangun menggunakan Laravel 12 sebagai framework aplikasi web dan Supabase sebagai backend untuk PostgreSQL, autentikasi, dan storage.

Selain aspek fungsional, aplikasi ini juga memperhatikan keamanan karena sistem menyimpan data akun pengguna, riwayat cuti, lampiran dokumen, dan data organisasi. Oleh karena itu SIMCUTI menerapkan autentikasi, otorisasi berbasis role, validasi input, proteksi CSRF, captcha, two-factor authentication, rate limiting, security headers, dan Row Level Security pada database.

### 1.2 Tujuan Aplikasi

Tujuan pengembangan aplikasi SIMCUTI adalah:

1. Mempermudah karyawan dalam mengajukan cuti secara online.
2. Mempermudah manager dan admin dalam menyetujui atau menolak pengajuan cuti.
3. Menyediakan informasi sisa cuti dan riwayat cuti karyawan.
4. Menyediakan pengelolaan data pengguna, departemen, dan jenis cuti.
5. Menyediakan laporan cuti yang dapat diekspor ke CSV, Excel, dan PDF.
6. Meningkatkan keamanan proses autentikasi dan akses data.
7. Menyediakan audit trail melalui activity log agar aktivitas penting dapat ditelusuri.

## Bab 2 Analisis Sistem

### 2.1 Kebutuhan Sistem

#### 2.1.1 Kebutuhan Fungsional

| No | Kebutuhan | Keterangan |
|---|---|---|
| 1 | Registrasi dan login | Pengguna dapat membuat akun dan masuk menggunakan email/password. |
| 2 | OAuth | Pengguna dapat login melalui Google atau GitHub. |
| 3 | Captcha | Login dan registrasi dilindungi captcha untuk mengurangi bot. |
| 4 | Two-Factor Authentication | Pengguna dapat mengaktifkan 2FA, lalu menerima kode verifikasi melalui email. |
| 5 | Manajemen cuti | Karyawan dapat membuat, melihat, mengedit, dan membatalkan pengajuan cuti. |
| 6 | Approval cuti | Manager atau admin dapat menyetujui dan menolak pengajuan cuti. |
| 7 | Dashboard | Sistem menampilkan statistik sesuai role pengguna. |
| 8 | Manajemen pengguna | Admin dapat mengelola data pengguna dan status aktif akun. |
| 9 | Manajemen departemen | Admin dapat mengelola data departemen. |
| 10 | Manajemen jenis cuti | Admin dapat mengelola jenis cuti dan batas maksimal hari per pengajuan. |
| 11 | Laporan | Admin dan manager dapat melihat serta mengekspor laporan. |
| 12 | Notifikasi | Sistem menyediakan notifikasi untuk pengguna. |
| 13 | Activity log | Sistem mencatat aktivitas penting seperti login, pengajuan, approval, dan perubahan password. |

#### 2.1.2 Kebutuhan Non-Fungsional

| No | Kebutuhan | Keterangan |
|---|---|---|
| 1 | Keamanan | Sistem harus membatasi akses berdasarkan role dan status autentikasi. |
| 2 | Validasi data | Input pengguna harus divalidasi sebelum diproses. |
| 3 | Ketersediaan | Aplikasi dapat dijalankan melalui web server dengan konfigurasi Laravel. |
| 4 | Auditabilitas | Aktivitas penting harus tersimpan pada activity log. |
| 5 | Integritas data | Database menggunakan constraint, foreign key, check constraint, dan trigger. |
| 6 | Privasi dokumen | Lampiran cuti disimpan pada private storage dan diakses menggunakan signed URL. |

#### 2.1.3 Kebutuhan Perangkat Lunak

| Komponen | Teknologi |
|---|---|
| Bahasa backend | PHP 8.2 atau lebih baru |
| Framework | Laravel 12 |
| Database | Supabase PostgreSQL |
| Autentikasi | Supabase Auth dan Laravel Session |
| Storage | Supabase Storage |
| Frontend | Blade, Tailwind CSS, Alpine.js, Chart.js |
| Build tool | Vite |
| Package manager | Composer dan NPM |
| Library laporan PDF | barryvdh/laravel-dompdf |

#### 2.1.4 Aktor Sistem

| Aktor | Hak Akses |
|---|---|
| Karyawan | Login, melihat dashboard, mengajukan cuti, melihat riwayat, mengubah profil, mengatur 2FA. |
| Manager | Semua akses karyawan, melihat pengajuan tim, menyetujui/menolak pengajuan tim, melihat laporan. |
| Admin | Mengelola pengguna, departemen, jenis cuti, semua pengajuan, laporan, dan activity log. |

## Bab 3 Implementasi

### 3.1 Struktur Database

Database utama menggunakan Supabase PostgreSQL. Struktur tabel yang digunakan adalah sebagai berikut:

| Tabel | Fungsi Utama |
|---|---|
| `profiles` | Menyimpan profil pengguna, role, departemen, jatah cuti, sisa cuti, status 2FA, status aktif, dan informasi login. |
| `departments` | Menyimpan data departemen, kode departemen, manager, deskripsi, dan status aktif. |
| `leave_types` | Menyimpan jenis cuti, kode cuti, batas maksimal hari, kebutuhan dokumen, dan status aktif. |
| `leave_requests` | Menyimpan pengajuan cuti, tanggal mulai, tanggal selesai, total hari, alasan, lampiran, status, approver, dan catatan approval. |
| `leave_balances` | Menyimpan saldo cuti tahunan per pengguna. |
| `activity_logs` | Menyimpan log aktivitas pengguna, aksi, deskripsi, IP address, user agent, dan waktu. |
| `captcha_sessions` | Menyimpan captcha sementara, session key, IP address, jumlah percobaan, dan waktu kedaluwarsa. |
| `two_factor_codes` | Menyimpan kode 2FA, status penggunaan, dan waktu kedaluwarsa. |
| `notifications` | Menyimpan notifikasi pengguna, pesan, tipe notifikasi, status baca, dan link. |
| `sessions` | Menyimpan session Laravel jika menggunakan driver database. |

### 3.2 Relasi Database

Relasi penting pada database:

1. `profiles.id` berelasi dengan `auth.users.id` dari Supabase Auth.
2. `profiles.department_id` berelasi dengan `departments.id`.
3. `departments.manager_id` berelasi dengan `profiles.id`.
4. `leave_requests.user_id` berelasi dengan `auth.users.id`.
5. `leave_requests.leave_type_id` berelasi dengan `leave_types.id`.
6. `leave_requests.disetujui_oleh` berelasi dengan `profiles.id`.
7. `leave_balances.user_id` berelasi dengan `auth.users.id`.
8. `activity_logs.user_id` berelasi dengan `auth.users.id`.
9. `notifications.user_id` berelasi dengan `auth.users.id`.

### 3.3 Constraint dan Trigger Database

Beberapa constraint dan trigger yang diterapkan:

| Implementasi | Tujuan |
|---|---|
| UUID primary key | Mengurangi prediktabilitas ID data. |
| Foreign key | Menjaga hubungan antar tabel tetap valid. |
| Check constraint pada role | Role hanya boleh `admin`, `manager`, atau `karyawan`. |
| Check constraint pada status cuti | Status hanya boleh `pending`, `disetujui`, `ditolak`, atau `dibatalkan`. |
| Check constraint tanggal cuti | Tanggal selesai tidak boleh lebih awal dari tanggal mulai. |
| Trigger `prevent_overlapping_leave` | Mencegah pengajuan cuti yang tanggalnya tumpang tindih pada level database. |
| Trigger update saldo cuti | Mengurangi saldo saat cuti disetujui dan mengembalikan saldo saat cuti dibatalkan/ditolak setelah disetujui. |
| Trigger `updated_at` | Memperbarui kolom `updated_at` otomatis saat data berubah. |
| Function cleanup captcha dan 2FA | Membersihkan data sementara yang sudah kedaluwarsa. |

### 3.4 Fitur Aplikasi

#### 3.4.1 Autentikasi

SIMCUTI mendukung autentikasi email/password melalui Supabase Auth. Pada proses login, sistem melakukan validasi email, password, captcha, status akun aktif, session regeneration, dan pencatatan aktivitas login.

#### 3.4.2 Registrasi

Pengguna dapat melakukan registrasi dengan nama lengkap, email, nomor telepon, password, captcha, dan persetujuan syarat penggunaan. Password minimal 8 karakter dan harus mengandung huruf besar, huruf kecil, angka, dan karakter spesial.

#### 3.4.3 OAuth

Aplikasi mendukung login OAuth melalui Google dan GitHub. Sistem menggunakan nonce yang disimpan pada server-side session untuk memastikan alur OAuth berasal dari sesi pengguna yang valid.

#### 3.4.4 Two-Factor Authentication

Pengguna dapat mengaktifkan 2FA. Saat login, sistem mengirim kode 6 digit melalui email. Kode memiliki masa berlaku 10 menit, hanya dapat digunakan sekali, dan proses verifikasi memiliki rate limit.

#### 3.4.5 Manajemen Pengajuan Cuti

Karyawan dapat mengajukan cuti dengan memilih jenis cuti, tanggal mulai, tanggal selesai, alasan, dan lampiran opsional. Sistem menghitung total hari otomatis, memeriksa saldo cuti, batas maksimal hari berdasarkan jenis cuti, dan mendeteksi tanggal yang tumpang tindih.

#### 3.4.6 Approval Cuti

Manager dan admin dapat menyetujui atau menolak pengajuan. Manager hanya dapat memproses pengajuan dari departemennya sendiri dan tidak dapat menyetujui pengajuan miliknya sendiri. Admin memiliki cakupan akses lebih luas.

#### 3.4.7 Manajemen Data Master

Admin dapat mengelola pengguna, departemen, dan jenis cuti. Data master memiliki status aktif serta mendukung soft delete melalui kolom `deleted_at`.

#### 3.4.8 Laporan

Admin dan manager dapat mengakses laporan cuti. Sistem mendukung ekspor laporan ke format CSV, Excel, dan PDF.

#### 3.4.9 Notifikasi

Sistem menyediakan notifikasi per pengguna, termasuk jumlah notifikasi yang belum dibaca.

#### 3.4.10 Activity Log

Aktivitas penting seperti login, logout, registrasi, pengajuan cuti, pembaruan cuti, approval, rejection, reset password, dan perubahan 2FA dicatat pada tabel `activity_logs`.

## Bab 4 Analisis Keamanan

### 4.1 OWASP yang Diterapkan

Analisis berikut menggunakan pendekatan OWASP Top 10 untuk melihat kontrol keamanan yang diterapkan pada SIMCUTI.

| OWASP Top 10 | Penerapan pada SIMCUTI |
|---|---|
| A01 Broken Access Control | Menggunakan middleware `supabase.auth`, `role`, dan `2fa`; route admin/manager dibatasi; manager hanya dapat mengakses data departemennya; database menerapkan Row Level Security. |
| A02 Cryptographic Failures | Password dikelola oleh Supabase Auth; token akses Supabase digunakan untuk autentikasi; lampiran private diakses dengan signed URL; session cookie menggunakan `http_only` dan `same_site`. |
| A03 Injection | Input divalidasi oleh Laravel; filter status di-whitelist; alasan cuti dibersihkan dengan `strip_tags`; query database dilakukan melalui Supabase API, bukan string SQL manual. |
| A04 Insecure Design | Workflow approval membatasi manager agar tidak menyetujui pengajuan sendiri; trigger database mencegah cuti tumpang tindih; saldo cuti dicek sebelum approval. |
| A05 Security Misconfiguration | Middleware security headers menambahkan CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, dan Permissions-Policy. |
| A06 Vulnerable and Outdated Components | Dependency dikelola menggunakan Composer dan NPM sehingga dapat diperbarui dan diaudit. |
| A07 Identification and Authentication Failures | Login dilindungi captcha, rate limiting, account lock sementara, session regeneration, 2FA, dan refresh token validation. |
| A08 Software and Data Integrity Failures | Constraint database, foreign key, trigger, dan RLS membantu menjaga integritas proses bisnis. |
| A09 Security Logging and Monitoring Failures | Activity log mencatat aktivitas penting dengan informasi user, aksi, model, IP address, dan user agent. |
| A10 Server-Side Request Forgery | Aplikasi tidak menyediakan fitur fetch URL bebas dari pengguna; akses eksternal dibatasi pada layanan Supabase/Auth/Storage yang dikonfigurasi. |

### 4.2 Mitigasi Keamanan

| Risiko | Mitigasi yang Diterapkan |
|---|---|
| Brute force login | Captcha, rate limiting berdasarkan IP dan email, lock sementara setelah 5 percobaan gagal. |
| Bot pada registrasi/login | Captcha dengan session key, batas percobaan, dan waktu kedaluwarsa 5 menit. |
| Session fixation | Session diregenerasi setelah login dan refresh token. |
| Akses tanpa login | Route utama dilindungi middleware `supabase.auth`. |
| Akses fitur oleh role tidak sah | Middleware `role:admin`, `role:manager`, dan pengecekan manual pada controller. |
| Bypass approval antar departemen | Manager hanya dapat memproses pengajuan dari departemen yang sama. |
| Pengajuan cuti ganda pada tanggal sama | Validasi aplikasi dan trigger database `prevent_overlapping_leave`. |
| Upload file berbahaya | Validasi ekstensi, MIME type, ukuran maksimal 5 MB, ukuran minimal 10 KB, dan pembatasan tipe file PDF/JPG/PNG. |
| Kebocoran metadata gambar | EXIF gambar JPEG/PNG dihapus sebelum upload. |
| Image bomb | Sistem mengecek jumlah pixel gambar maksimal 50 megapixel sebelum pemrosesan GD. |
| Kebocoran lampiran | File disimpan di bucket private dan dibaca melalui signed URL. |
| CSRF | Form Blade menggunakan `@csrf` dan request AJAX mengirim header `X-CSRF-TOKEN`. |
| XSS | Output Blade di-escape secara default, input alasan diproses dengan `strip_tags`, dan CSP diterapkan. |
| Clickjacking | Header `X-Frame-Options: DENY` dan CSP `frame-ancestors 'none'`. |
| MIME sniffing | Header `X-Content-Type-Options: nosniff`. |
| Penyalahgunaan 2FA | Kode 2FA hanya berlaku 10 menit, sekali pakai, diverifikasi dengan `hash_equals`, dan memiliki rate limit. |
| Email enumeration | Registrasi dan reset password menggunakan pesan generik agar email valid tidak mudah ditebak. |
| Data leakage antar user | Row Level Security membatasi data berdasarkan user, role, dan departemen. |

### 4.3 Catatan Keamanan

Beberapa perbaikan hardening keamanan yang telah diterapkan:

1. Route `/clear-cache` sudah tidak dibuka sebagai endpoint publik. Endpoint ini diubah menjadi `POST /clear-cache` dan ditempatkan di dalam middleware `supabase.auth`, `2fa`, dan `role:admin`, sehingga hanya admin yang sudah login dan lolos 2FA yang dapat mengaksesnya.
2. Content Security Policy utama masih mempertahankan `'unsafe-inline'` dan `'unsafe-eval'` agar tampilan Blade, CDN Tailwind, tombol inline, modal, dan script dashboard tetap berjalan. Sebagai langkah aman bertahap, aplikasi menambahkan `Content-Security-Policy-Report-Only` yang lebih ketat di production untuk mengevaluasi script/style mana yang perlu direfactor sebelum CSP enforced diperketat.
3. Konfigurasi `SESSION_SECURE_COOKIE` sekarang otomatis bernilai `true` saat `APP_ENV=production`, tetapi tetap bisa dioverride melalui environment variable jika diperlukan.
4. Audit dependency ditambahkan melalui script `composer audit:security` untuk Composer dan `npm run audit:security` untuk NPM agar pemeriksaan dependency dapat dilakukan berkala.

## Bab 5 Pengujian

### 5.1 Hasil Pengujian Keamanan

Pengujian keamanan dilakukan berdasarkan pemeriksaan alur aplikasi dan implementasi kode. Hasil pengujian dirangkum sebagai berikut:

| No | Skenario Pengujian | Hasil yang Diharapkan | Hasil |
|---|---|---|---|
| 1 | Login dengan captcha salah | Login ditolak dan muncul pesan captcha tidak valid. | Berhasil ditolak. |
| 2 | Login gagal lebih dari 5 kali | Akun/kombinasi IP-email dikunci sementara 15 menit. | Berhasil dibatasi. |
| 3 | Login berhasil | Session ID diregenerasi dan data session pengguna disimpan. | Berhasil. |
| 4 | Akses dashboard tanpa login | Pengguna diarahkan ke halaman login. | Berhasil dibatasi. |
| 5 | Akses halaman admin sebagai karyawan | Sistem mengembalikan 403 Forbidden. | Berhasil dibatasi. |
| 6 | Manager mengakses pengajuan departemen lain | Sistem menolak akses. | Berhasil dibatasi. |
| 7 | Manager menyetujui pengajuan sendiri | Sistem menolak proses approval. | Berhasil dibatasi. |
| 8 | Mengajukan cuti dengan tanggal selesai sebelum tanggal mulai | Validasi menolak input. | Berhasil ditolak. |
| 9 | Mengajukan cuti melebihi saldo | Sistem menolak pengajuan atau approval. | Berhasil ditolak. |
| 10 | Mengajukan cuti pada tanggal yang tumpang tindih | Validasi aplikasi dan trigger database menolak data. | Berhasil ditolak. |
| 11 | Upload file selain PDF/JPG/PNG | Upload ditolak. | Berhasil ditolak. |
| 12 | Upload file terlalu besar | Upload ditolak jika melebihi 5 MB. | Berhasil ditolak. |
| 13 | Upload gambar dengan metadata EXIF | Metadata dihapus sebelum file disimpan. | Berhasil dimitigasi. |
| 14 | Verifikasi 2FA dengan kode salah berulang | Percobaan dibatasi dan dikunci sementara. | Berhasil dibatasi. |
| 15 | Verifikasi 2FA dengan kode kedaluwarsa | Kode ditolak dan pengguna diminta mengirim ulang kode. | Berhasil ditolak. |
| 16 | CSRF pada form POST | Request tanpa token CSRF ditolak oleh middleware Laravel. | Berhasil dilindungi. |
| 17 | XSS sederhana pada alasan cuti | Tag HTML dihapus dengan `strip_tags` dan output Blade di-escape. | Berhasil dimitigasi. |
| 18 | Clickjacking | Browser tidak boleh memuat aplikasi dalam frame. | Berhasil dimitigasi dengan security header. |

### 5.2 Pengujian Fungsional

| No | Fitur | Hasil |
|---|---|---|
| 1 | Registrasi pengguna baru | Pengguna dapat mendaftar dengan role default `karyawan`. |
| 2 | Login email/password | Pengguna dapat login jika kredensial benar dan akun aktif. |
| 3 | OAuth Google/GitHub | Sistem menyediakan route OAuth dan callback. |
| 4 | Pengajuan cuti | Pengguna dapat membuat pengajuan cuti valid. |
| 5 | Edit pengajuan cuti | Pengguna hanya dapat mengedit pengajuan miliknya yang masih `pending`. |
| 6 | Batalkan pengajuan | Pengguna dapat membatalkan pengajuan miliknya yang masih `pending`. |
| 7 | Approval manager/admin | Manager/admin dapat menyetujui atau menolak sesuai hak akses. |
| 8 | Laporan | Admin/manager dapat membuka laporan dan ekspor data. |
| 9 | Notifikasi | Pengguna dapat melihat notifikasi dan jumlah belum dibaca. |
| 10 | Activity log | Aktivitas penting tersimpan sebagai audit trail. |

## Bab 6 Kesimpulan

SIMCUTI merupakan aplikasi sistem informasi manajemen cuti yang membantu proses pengajuan, persetujuan, pencatatan saldo, laporan, dan audit aktivitas cuti karyawan. Aplikasi ini membagi hak akses menjadi tiga role utama, yaitu admin, manager, dan karyawan, sehingga alur kerja cuti dapat berjalan sesuai struktur organisasi.

Dari sisi implementasi, SIMCUTI menggunakan Laravel 12, Supabase PostgreSQL, Supabase Auth, Supabase Storage, Tailwind CSS, dan Vite. Database dirancang dengan relasi, constraint, trigger, Row Level Security, serta tabel pendukung seperti activity log, captcha session, two-factor code, dan notification.

Dari sisi keamanan, aplikasi telah menerapkan beberapa kontrol penting seperti validasi input, captcha, rate limiting, 2FA, session regeneration, CSRF protection, RBAC, RLS, private storage, signed URL, validasi upload file, security headers, dan activity logging. Kontrol tersebut sudah sesuai dengan beberapa kategori OWASP Top 10, terutama Broken Access Control, Injection, Identification and Authentication Failures, Security Misconfiguration, dan Security Logging and Monitoring Failures.

Secara umum, SIMCUTI sudah memenuhi kebutuhan utama aplikasi manajemen cuti dan memiliki dasar keamanan yang baik. Perbaikan yang disarankan sebelum produksi adalah membatasi atau menghapus route `/clear-cache`, memperketat CSP agar tidak menggunakan `'unsafe-inline'` dan `'unsafe-eval'`, mengaktifkan secure cookie pada HTTPS, serta menjalankan audit dependency secara berkala.
