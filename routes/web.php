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

    Route::get('/notifikasi/{notifikasi}/buka', function (\Illuminate\Notifications\DatabaseNotification $notifikasi) {
        abort_unless($notifikasi->notifiable_id === auth()->id(), 403);

        $notifikasi->markAsRead();

        return redirect($notifikasi->data['url'] ?? route('login'));
    })->name('notifikasi.buka');
});

Route::middleware(['auth', 'role:admin_departemen'])->group(function () {
    Route::get('/dispensasi/create', [DispensasiController::class, 'create'])->name('dispensasi.create');
    Route::post('/dispensasi', [DispensasiController::class, 'store'])->name('dispensasi.store');
    Route::get('/dispensasi', [DispensasiController::class, 'index'])->name('dispensasi.index');
    Route::get('/dispensasi/export-pdf', [DispensasiController::class, 'exportPdf'])->name('dispensasi.export.pdf');
    Route::get('/dispensasi/{dispensasi}', [DispensasiController::class, 'show'])->name('dispensasi.show');
});

Route::middleware([
    'auth',
    'role:manajer_departemen,senior_manajer_sekper,kepala_spi,direktur_teknik,direktur_administrasi_keuangan,direktur_utama',
])->group(function () {

    // Route generik untuk daftar & aksi approval — dipakai oleh view
    Route::get('/persetujuan', [ApprovalController::class, 'index'])->name('approval.index');
    Route::get('/persetujuan/{dispensasi}', [ApprovalController::class, 'show'])->name('approval.show');
    Route::post('/dispensasi/{dispensasi}/approve', [ApprovalController::class, 'approve'])->name('approval.approve');
    Route::post('/dispensasi/{dispensasi}/reject', [ApprovalController::class, 'reject'])->name('approval.reject');

    // Route dashboard per role — dipakai oleh Auth::user()->dashboardRoute()
    Route::get('/manajer/dashboard', [ApprovalController::class, 'index'])->name('dashboard.manajer');
    Route::get('/sekretaris-perusahaan/dashboard', [ApprovalController::class, 'index'])->name('dashboard.senior-manajer-sekper');
    Route::get('/spi/dashboard', [ApprovalController::class, 'index'])->name('dashboard.kepala-spi');
    Route::get('/direktur-teknik/dashboard', [ApprovalController::class, 'index'])->name('dashboard.direktur-teknik');
    Route::get('/direktur-administrasi-keuangan/dashboard', [ApprovalController::class, 'index'])->name('dashboard.direktur-administrasi-keuangan');
    Route::get('/direktur-utama/dashboard', [ApprovalController::class, 'index'])->name('dashboard.direktur-utama');
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

    Route::resource('pengguna', UserController::class)->except(['show']);
});