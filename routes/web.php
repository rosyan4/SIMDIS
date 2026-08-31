<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\DispensasiController;
use App\Http\Controllers\PasswordChangeController;
use App\Http\Controllers\Sdm\DashboardController;
use App\Http\Controllers\Sdm\DepartemenController;
use App\Http\Controllers\Sdm\MonitoringController;
use App\Http\Controllers\Sdm\PegawaiController;
use App\Http\Controllers\Sdm\PegawaiImportController;
use App\Http\Controllers\Sdm\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect(auth()->check() ? auth()->user()->dashboardRoute() : route('login'));
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/ganti-password', [PasswordChangeController::class, 'showForm'])->name('password.change.form');
    Route::post('/ganti-password', [PasswordChangeController::class, 'update'])->name('password.change.update');

    Route::post('/notifikasi/mark-all-read', function () {
        auth()->user()->unreadNotifications->markAsRead();
        return response()->json(['ok' => true]);
    })->name('notifikasi.markAllRead');
});

// Admin Departemen: menginput dispensasi ATAS NAMA pegawai (KF-09) — Pegawai
// sendiri tidak punya akun/login, jadi ini BUKAN role 'pegawai' (tidak ada
// role itu di skema kita).
Route::middleware(['auth', 'role:admin_departemen'])->group(function () {
    Route::get('/dispensasi/create', [DispensasiController::class, 'create'])->name('dispensasi.create');
    Route::post('/dispensasi', [DispensasiController::class, 'store'])->name('dispensasi.store');
    Route::get('/dispensasi', [DispensasiController::class, 'index'])->name('dispensasi.index');
    Route::get('/dispensasi/{dispensasi}', [DispensasiController::class, 'show'])->name('dispensasi.show');

    // Alias: dashboardRoute() di User model mengarahkan admin_departemen ke sini.
    // Riwayat dispensasi ITU dashboard-nya, sama seperti pola dashboard.manajer/
    // dashboard.asmen di bawah — bukan halaman kosong terpisah.
    Route::get('/admin-departemen/dashboard', [DispensasiController::class, 'index'])->name('dashboard.admin-departemen');
});

Route::middleware(['auth', 'role:manajer_departemen'])->group(function () {
    Route::get('/manajer/dashboard', [ApprovalController::class, 'indexManajer'])->name('dashboard.manajer');
});

Route::middleware(['auth', 'role:asisten_manajer'])->group(function () {
    Route::get('/asmen/dashboard', [ApprovalController::class, 'indexAsmen'])->name('dashboard.asmen');
});

Route::middleware(['auth', 'role:manajer_departemen,asisten_manajer'])->group(function () {
    Route::get('/persetujuan/{dispensasi}', [ApprovalController::class, 'show'])->name('approval.show');
    Route::post('/dispensasi/{dispensasi}/approve', [ApprovalController::class, 'approve'])->name('dispensasi.approve');
    Route::post('/dispensasi/{dispensasi}/reject', [ApprovalController::class, 'reject'])->name('dispensasi.reject');
});

Route::middleware(['auth', 'role:admin_sdm'])->prefix('sdm')->name('sdm.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('pegawai', PegawaiController::class)->except(['show']);
    Route::get('/pegawai-import', [PegawaiImportController::class, 'form'])->name('pegawai.import.form');
    Route::post('/pegawai-import/preview', [PegawaiImportController::class, 'preview'])->name('pegawai.import.preview');
    Route::post('/pegawai-import/confirm', [PegawaiImportController::class, 'confirm'])->name('pegawai.import.confirm');

    Route::get('/departemen', [DepartemenController::class, 'index'])->name('departemen.index');
    Route::get('/departemen/{id}', [DepartemenController::class, 'show'])->name('departemen.show');

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/export-excel', [MonitoringController::class, 'exportExcel'])->name('monitoring.export.excel');
    // export-pdf DIHAPUS SEMENTARA — MonitoringController::exportPdf() belum
    // pernah kita buat. Tambahkan lagi kalau method-nya sudah ada.

    // Kelola Data Pengguna — UserController yang sebelumnya belum terdaftar sama sekali.
    Route::resource('pengguna', UserController::class)->except(['show']);
    Route::post('/pengguna/{pengguna}/status-manajer', [UserController::class, 'updateStatusManajer'])
        ->name('pengguna.status-manajer');
});