<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CaptchaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\LeaveTypeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Nampilin halaman login di root (biar nggak kena redirect 302 yang bisa ngilangin URL fragment kayak access_token dari Supabase)
Route::get('/', [AuthController::class, 'showLogin']);

// ============================================
// GUEST ROUTES (Rute buat yang belum login)
// ============================================
Route::middleware('guest')->group(function () {
    // Autentikasi / Login
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    // Pendaftaran Akun / Register
    Route::get('/register', [RegisterController::class, 'showRegister'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.post');

    // Lupa Password / Reset Password
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPassword'])->name('forgot-password');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])->name('forgot-password.post');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetPassword'])->name('reset-password');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('reset-password.post');
});

// Captcha (bisa diakses publik)
Route::get('/captcha', [CaptchaController::class, 'generate'])->name('captcha');
Route::get('/captcha/refresh', [CaptchaController::class, 'refresh'])->name('captcha.refresh');

// OAuth login pakai Google/Github (bisa diakses publik)
Route::get('/oauth-callback', [AuthController::class, 'oauthCallback'])->name('oauth.callback');
Route::post('/oauth-handle', [AuthController::class, 'oauthHandle'])->name('oauth.handle');
Route::get('/oauth/{provider}', [AuthController::class, 'oauthRedirect'])->name('oauth.redirect')->where('provider', 'github|google');

// ============================================
// 2FA ROUTES (Udah login tapi belum verifikasi 2 Langkah)
// ============================================
Route::middleware('supabase.auth')->group(function () {
    // Proses dan halaman 2FA
    Route::get('/2fa', [TwoFactorController::class, 'show'])->name('2fa.show');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify');
    Route::post('/2fa/resend', [TwoFactorController::class, 'resend'])->name('2fa.resend');
    
    // Logout buat keluar dari sistem
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

// ============================================
// AUTHENTICATED ROUTES (Udah login dan beres verifikasi 2FA)
// ============================================
Route::middleware(['supabase.auth', '2fa'])->group(function () {

    // Halaman Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ------------------------------------------
    // SEMUA ROLE: Pengelolaan Cuti (Leave Management)
    // ------------------------------------------
    Route::prefix('leave')->name('leave.')->group(function () {
        Route::get('/', [LeaveRequestController::class, 'index'])->name('index');
        Route::get('/create', [LeaveRequestController::class, 'create'])->name('create');
        Route::get('/history/list', [LeaveRequestController::class, 'history'])->name('history');
        Route::get('/employee-requests', [LeaveRequestController::class, 'employeeRequests'])->name('employee-requests');
        Route::get('/pending/list', [LeaveRequestController::class, 'pending'])->name('pending');
        Route::post('/store', [LeaveRequestController::class, 'store'])->name('store');
        Route::get('/{id}', [LeaveRequestController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [LeaveRequestController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LeaveRequestController::class, 'update'])->name('update');
        Route::post('/{id}/cancel', [LeaveRequestController::class, 'cancel'])->name('cancel');
        Route::post('/{id}/approve', [LeaveRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [LeaveRequestController::class, 'reject'])->name('reject');
    });

    // Manajer: Ngelihat data anggota timnya
    Route::middleware('role:manager')->group(function () {
        Route::get('/team', [DashboardController::class, 'team'])->name('team');
    });

    // Pengaturan Profil (edit, ganti/hapus foto, dan hapus akun)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Halaman Pengaturan (Settings)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings/2fa', [SettingsController::class, 'toggle2FA'])->name('settings.2fa');
    Route::put('/settings/password', [SettingsController::class, 'changePassword'])
        ->name('settings.password')
        ->middleware('throttle:5,15'); // Dibatasi maksimal 5 percobaan tiap 15 menit

    // Notifikasi (ngelihat dan tandai udah dibaca)
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::get('/notifications/count', [NotificationController::class, 'count'])->name('notifications.count');

    // Laporan (Cuma Admin & Manajer yang bisa akses)
    Route::middleware('role:admin,manager')->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export/{format}', [ReportController::class, 'export'])->name('export');
    });

    // ------------------------------------------
    // KHUSUS ADMIN: Data Master & Catatan Aktivitas
    // ------------------------------------------
    Route::middleware('role:admin')->group(function () {
        // Kelola daftar pengguna (Users CRUD)
        Route::resource('users', UserController::class)->where(['user' => '[0-9a-f\-]{36}']);
        Route::post('/users/{id}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active')->where('id', '[0-9a-f\-]{36}');

        // Kelola departemen (Departments CRUD)
        Route::resource('departments', DepartmentController::class)->where(['department' => '[0-9a-f\-]{36}']);

        // Kelola tipe-tipe cuti (Leave Types CRUD)
        Route::resource('leave-types', LeaveTypeController::class)->where(['leave_type' => '[0-9a-f\-]{36}']);

        // Log Aktivitas Pengguna di sistem
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/data', [ActivityLogController::class, 'data'])->name('activity-logs.data');

        // Maintenance: endpoint khusus admin buat bersihin cache. Pastiin tetap terlindungi kalau di production.
        Route::post('/clear-cache', function() {
            \Illuminate\Support\Facades\Artisan::call('optimize:clear');
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }
            return response()->json(['message' => 'All caches and OPCache have been cleared.']);
        })->name('maintenance.clear-cache');
    });
});
