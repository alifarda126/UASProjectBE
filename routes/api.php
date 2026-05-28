<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrganisasiController;
use App\Http\Controllers\TransaksiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProfilController;
use App\Http\Controllers\AgendaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminOrganisasiController;
use App\Http\Controllers\BandingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KasAnggotaController;
use App\Http\Controllers\ProgramAnggaranController;

/*
|--------------------------------------------------------------------------
| API Routes — MoneFlo Backend
|--------------------------------------------------------------------------
*/

/* ══════════════════════════════════════════════════
   AUTH ROUTES (Publik)
   ══════════════════════════════════════════════════ */
Route::get('/settings', [AdminOrganisasiController::class, 'getSettings'])->name('settings.get');
Route::get('/auth/google/redirect',  [SocialiteController::class, 'redirectToProvider'])->name('auth.google.redirect');
Route::get('/auth/google/callback',  [SocialiteController::class, 'handleProviderCallback'])->name('auth.google.callback');
Route::post('/auth/login', [SocialiteController::class, 'loginWithCredentials'])->name('auth.login');
Route::post('/auth/register', [SocialiteController::class, 'register'])->name('auth.register');
Route::post('/auth/send-otp', [\App\Http\Controllers\Auth\OtpController::class, 'send'])->name('auth.send-otp');
Route::post('/auth/verify-otp', [\App\Http\Controllers\Auth\OtpController::class, 'verify'])->name('auth.verify-otp');
Route::post('/auth/forgot-password', [\App\Http\Controllers\Auth\OtpController::class, 'resetPassword'])->name('auth.forgot-password');

/* ══════════════════════════════════════════════════
   PROTECTED ROUTES
   ══════════════════════════════════════════════════ */
Route::middleware(['auth.cookie', \App\Http\Middleware\TrackUserSession::class])->group(function () {

    Route::get('/user', [SocialiteController::class, 'getAuthenticatedUser'])->name('user.me');
    Route::get('/user/session', [UserController::class, 'getCurrentSession'])->name('user.session');
    Route::post('/logout', [SocialiteController::class, 'logout'])->name('auth.logout');

    Route::prefix('profil')->name('profil.')->group(function () {
        Route::get('/', [ProfilController::class, 'show'])->name('show');
        Route::put('/', [ProfilController::class, 'update'])->name('update');
        Route::post('/avatar', [ProfilController::class, 'uploadAvatar'])->name('avatar');
        Route::post('/password', [ProfilController::class, 'changePassword'])->name('password');
    });

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
        Route::get('/chart', [DashboardController::class, 'chartData'])->name('chart');
        Route::get('/recent-transactions', [DashboardController::class, 'recentTransactions'])->name('recent');
        Route::get('/upcoming-agendas', [DashboardController::class, 'upcomingAgendas'])->name('agendas');
    });

    Route::apiResource('organisasi', OrganisasiController::class);
    Route::prefix('organisasi/{organisasi}')->name('organisasi.')->group(function () {
        Route::post('/anggota', [OrganisasiController::class, 'addAnggota'])->name('anggota.add');
        Route::delete('/anggota/{user}', [OrganisasiController::class, 'removeAnggota'])->name('anggota.remove');
        Route::put('/anggota/{user}/role', [OrganisasiController::class, 'updateRoleAnggota'])->name('anggota.role');
        Route::post('/reactivate', [OrganisasiController::class, 'reactivate'])->name('reactivate');
        Route::post('/logo', [OrganisasiController::class, 'uploadLogo'])->name('logo');
        Route::delete('/logo', [OrganisasiController::class, 'deleteLogo'])->name('logo.delete');
    });

    Route::apiResource('transaksi', TransaksiController::class);
    Route::post('/transaksi/{transaksi}/approve', [TransaksiController::class, 'approve'])->name('transaksi.approve');
    Route::post('/transaksi/{transaksi}/reject', [TransaksiController::class, 'reject'])->name('transaksi.reject');
    Route::get('/transaksi-export', [TransaksiController::class, 'export'])->name('transaksi.export');

    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/keuangan', [LaporanController::class, 'keuangan'])->name('keuangan');
        Route::get('/export-csv', [LaporanController::class, 'exportCsv'])->name('export.csv');
    });

    Route::apiResource('agendas', AgendaController::class);

    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('bandings')->name('bandings.')->group(function () {
        Route::get('/', [BandingController::class, 'index'])->name('index');
        Route::post('/', [BandingController::class, 'store'])->name('store');
    });

    Route::prefix('kas-anggota')->name('kas-anggota.')->group(function () {
        Route::get('/', [KasAnggotaController::class, 'index'])->name('index');
        Route::post('/', [KasAnggotaController::class, 'store'])->name('store');
        Route::delete('/{kasAnggota}', [KasAnggotaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('program-anggaran')->name('program-anggaran.')->group(function () {
        Route::get('/', [ProgramAnggaranController::class, 'index'])->name('index');
        Route::post('/sync', [ProgramAnggaranController::class, 'sync'])->name('sync');
    });

    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', function () {
            return response()->json(['data' => \App\Models\User::select(['id', 'name', 'email', 'role', 'is_active', 'created_at', 'last_login_at'])->orderBy('created_at', 'desc')->get()]);
        })->name('users');

        Route::patch('/users/{user}/toggle-active', function (\App\Models\User $user) {
            $user->update(['is_active' => !$user->is_active]);
            return response()->json(['message' => 'Status user berhasil diupdate', 'is_active' => $user->is_active]);
        })->name('users.toggle');

        Route::get('/organisasi', [AdminOrganisasiController::class, 'index'])->name('organisasi');
        Route::post('/organisasi', [AdminOrganisasiController::class, 'store'])->name('organisasi.store');
        Route::delete('/organisasi/{id}', [AdminOrganisasiController::class, 'forceDestroy'])->name('organisasi.destroy');
        Route::post('/organisasi/{id}/suspend', [AdminOrganisasiController::class, 'suspend'])->name('organisasi.suspend');
        Route::post('/organisasi/{id}/unsuspend', [AdminOrganisasiController::class, 'unsuspend'])->name('organisasi.unsuspend');
        Route::get('/bandings', [AdminOrganisasiController::class, 'bandings'])->name('bandings');
        Route::post('/bandings/{id}/resolve', [AdminOrganisasiController::class, 'resolveBanding'])->name('bandings.resolve');

        Route::get('/stats', function () {
            return response()->json([
                'total_users' => \App\Models\User::count(),
                'total_organisasi' => \App\Models\Organisasi::count(),
                'total_transaksi' => \App\Models\Transaksi::count(),
                'pending_transaksi' => \App\Models\Transaksi::pending()->count(),
                'total_pemasukan' => (float) \App\Models\Transaksi::approved()->pemasukan()->sum('amount'),
                'total_pengeluaran' => (float) \App\Models\Transaksi::approved()->pengeluaran()->sum('amount'),
            ]);
        })->name('stats');

        Route::get('/laporan/keuangan', [AdminOrganisasiController::class, 'laporanKeuangan'])->name('laporan.keuangan');
        Route::post('/settings', [AdminOrganisasiController::class, 'saveSettings'])->name('settings.save');
    });
});

/* ══════════════════════════════════════════════════
   ROUTE DARURAT: DOWNLOAD DATA STORAGE
   ══════════════════════════════════════════════════ */
Route::get('/download-semua-data', function () {
    $zip = new ZipArchive;
    $zipFileName = 'backup_storage_' . date('Y-m-d_His') . '.zip';
    $zipPath = storage_path($zipFileName);

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        $files = Storage::disk('public')->allFiles();
        foreach ($files as $file) {
            $zip->addFile(storage_path('app/public/' . $file), $file);
        }
        $zip->close();
    }

    if (file_exists($zipPath)) {
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
    return response()->json(['message' => 'Gagal membuat file ZIP. Pastikan ada file di folder storage.'], 404);
});