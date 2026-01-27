<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PemasokController;
use App\Http\Controllers\PersediaanController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PembelianController;
use App\Http\Controllers\AkunController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\BukuBesarController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PerusahaanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PenerimaanController;
use App\Http\Controllers\PembayaranController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\ProyekController;
use App\Http\Controllers\JurnalKasController;

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // =====================================================
    // DASHBOARD - All authenticated users
    // =====================================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // =====================================================
    // MASTER DATA - Read access for manajer+, Write access for admin+
    // =====================================================

    // Write routes for admin+ (create, store, edit, update, destroy)
    // NOTE: These must be registered BEFORE the {param} routes to prevent route conflicts
    Route::middleware('role:superuser,admin')->group(function () {
        Route::get('pelanggan/create', [PelangganController::class, 'create'])->name('pelanggan.create');
        Route::post('pelanggan', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::get('pelanggan/{pelanggan}/edit', [PelangganController::class, 'edit'])->name('pelanggan.edit');
        Route::put('pelanggan/{pelanggan}', [PelangganController::class, 'update'])->name('pelanggan.update');
        Route::delete('pelanggan/{pelanggan}', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');

        Route::get('pemasok/create', [PemasokController::class, 'create'])->name('pemasok.create');
        Route::post('pemasok', [PemasokController::class, 'store'])->name('pemasok.store');
        Route::get('pemasok/{pemasok}/edit', [PemasokController::class, 'edit'])->name('pemasok.edit');
        Route::put('pemasok/{pemasok}', [PemasokController::class, 'update'])->name('pemasok.update');
        Route::delete('pemasok/{pemasok}', [PemasokController::class, 'destroy'])->name('pemasok.destroy');

        Route::get('persediaan/create', [PersediaanController::class, 'create'])->name('persediaan.create');
        Route::post('persediaan', [PersediaanController::class, 'store'])->name('persediaan.store');
        Route::get('persediaan/{persediaan}/edit', [PersediaanController::class, 'edit'])->name('persediaan.edit');
        Route::put('persediaan/{persediaan}', [PersediaanController::class, 'update'])->name('persediaan.update');
        Route::delete('persediaan/{persediaan}', [PersediaanController::class, 'destroy'])->name('persediaan.destroy');

        Route::get('akun/create', [AkunController::class, 'create'])->name('akun.create');
        Route::post('akun', [AkunController::class, 'store'])->name('akun.store');
        Route::get('akun/{akun}/edit', [AkunController::class, 'edit'])->name('akun.edit');
        Route::put('akun/{akun}', [AkunController::class, 'update'])->name('akun.update');
        Route::delete('akun/{akun}', [AkunController::class, 'destroy'])->name('akun.destroy');
    });

    // Read-only routes for manajer (index, show)
    // NOTE: These wildcard routes must come AFTER the /create routes
    Route::middleware('role:superuser,admin,manajer')->group(function () {
        Route::get('pelanggan', [PelangganController::class, 'index'])->name('pelanggan.index');
        Route::get('pelanggan/{pelanggan}', [PelangganController::class, 'show'])->name('pelanggan.show');
        Route::get('pemasok', [PemasokController::class, 'index'])->name('pemasok.index');
        Route::get('pemasok/{pemasok}', [PemasokController::class, 'show'])->name('pemasok.show');
        Route::get('persediaan', [PersediaanController::class, 'index'])->name('persediaan.index');
        Route::get('akun', [AkunController::class, 'index'])->name('akun.index');
    });

    // =====================================================
    // TRANSAKSI - All authenticated users
    // =====================================================
    Route::resource('penjualan', PenjualanController::class);
    Route::resource('pembelian', PembelianController::class);
    Route::resource('jurnal', JurnalController::class);
    Route::resource('penerimaan', PenerimaanController::class);
    Route::resource('pembayaran', PembayaranController::class);
    Route::get('kas', [KasController::class, 'index'])->name('kas.index');
    Route::get('kas/transfer', [KasController::class, 'transfer'])->name('kas.transfer');
    Route::post('kas/transfer', [KasController::class, 'storeTransfer'])->name('kas.storeTransfer');

    // =====================================================
    // PROYEK MANAGEMENT - All authenticated users
    // =====================================================
    Route::resource('proyek', ProyekController::class);
    Route::get('proyek-assign', [ProyekController::class, 'assignTransaksi'])->name('proyek.assign');
    Route::post('proyek-assign', [ProyekController::class, 'processBulkAssign'])->name('proyek.processBulkAssign');

    // =====================================================
    // JURNAL KAS - All authenticated users
    // =====================================================
    Route::resource('jurnal-kas', JurnalKasController::class);

    // =====================================================
    // LAPORAN - Semua role (termasuk staff)
    // =====================================================
    Route::middleware('role:superuser,admin,manajer,staff')->group(function () {
        Route::get('bukubesar', [BukuBesarController::class, 'index'])->name('bukubesar.index');
        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/neraca', [LaporanController::class, 'neraca'])->name('laporan.neraca');
        Route::get('/laporan/neraca/pdf', [LaporanController::class, 'neracaPdf'])->name('laporan.neraca.pdf');
        Route::get('/laporan/labarugi', [LaporanController::class, 'labaRugi'])->name('laporan.labarugi');
        Route::get('/laporan/labarugi/pdf', [LaporanController::class, 'labaRugiPdf'])->name('laporan.labarugi.pdf');
        Route::get('/laporan/labarugi-proyek', [LaporanController::class, 'labaRugiProyek'])->name('laporan.labarugi_proyek');
        Route::get('/laporan/labarugi-konsolidasi', [LaporanController::class, 'labaRugiKonsolidasi'])->name('laporan.labarugi_konsolidasi');
        Route::get('/laporan/aruskas-langsung', [LaporanController::class, 'arusKasLangsung'])->name('laporan.aruskas_langsung');
        Route::get('/laporan/aruskas-tidak-langsung', [LaporanController::class, 'arusKasTidakLangsung'])->name('laporan.aruskas_tidak_langsung');
        Route::get('/laporan/aruskas-proyek', [LaporanController::class, 'arusKasProyek'])->name('laporan.aruskas_proyek');
        Route::get('/laporan/aruskas-konsolidasi', [LaporanController::class, 'arusKasKonsolidasi'])->name('laporan.aruskas_konsolidasi');
        Route::get('/laporan/perubahan-ekuitas', [LaporanController::class, 'perubahanEkuitas'])->name('laporan.perubahan_ekuitas');
        Route::get('/laporan/persediaan', [LaporanController::class, 'persediaan'])->name('laporan.persediaan');
    });

    // =====================================================
    // PENGATURAN - Admin, Superuser
    // =====================================================
    Route::middleware('role:superuser,admin')->group(function () {
        Route::get('perusahaan', [PerusahaanController::class, 'edit'])->name('perusahaan.edit');
        Route::put('perusahaan', [PerusahaanController::class, 'update'])->name('perusahaan.update');
        Route::resource('users', UserController::class);
    });

    // =====================================================
    // DATABASE MANAGEMENT - Superuser Only
    // =====================================================
    Route::middleware('role:superuser')->group(function () {
        Route::get('database', [\App\Http\Controllers\DatabaseController::class, 'index'])->name('database.index');
        Route::post('database/truncate', [\App\Http\Controllers\DatabaseController::class, 'truncate'])->name('database.truncate');
        Route::post('database/fresh', [\App\Http\Controllers\DatabaseController::class, 'fresh'])->name('database.fresh');
        Route::post('database/drop', [\App\Http\Controllers\DatabaseController::class, 'drop'])->name('database.drop');
        Route::post('database/seed', [\App\Http\Controllers\DatabaseController::class, 'seed'])->name('database.seed');
    });

    // =====================================================
    // IMPORT & EXPORT DATA - Manajer, Admin, Superuser
    // =====================================================
    Route::middleware('role:superuser,admin,manajer')->group(function () {
        Route::get('import-export', [\App\Http\Controllers\ImportExportController::class, 'index'])->name('import-export.index');
        Route::get('import-export/export/{module}', [\App\Http\Controllers\ImportExportController::class, 'export'])->name('import-export.export');
        Route::get('import-export/template/{module}', [\App\Http\Controllers\ImportExportController::class, 'template'])->name('import-export.template');
        Route::post('import-export/import/{module}', [\App\Http\Controllers\ImportExportController::class, 'import'])->name('import-export.import');
        Route::get('import-export/export-all', [\App\Http\Controllers\ImportExportController::class, 'exportAll'])->name('import-export.export-all');

        // Import Kas Routes
        Route::get('import-kas', [\App\Http\Controllers\ImportKasController::class, 'index'])->name('import-kas.index');
        Route::post('import-kas/upload', [\App\Http\Controllers\ImportKasController::class, 'upload'])->name('import-kas.upload');
        Route::get('import-kas/review', [\App\Http\Controllers\ImportKasController::class, 'review'])->name('import-kas.review');
        Route::post('import-kas/update-selection', [\App\Http\Controllers\ImportKasController::class, 'updateSelection'])->name('import-kas.update-selection');
        Route::post('import-kas/update-akun', [\App\Http\Controllers\ImportKasController::class, 'updateAkun'])->name('import-kas.update-akun');
        Route::post('import-kas/bulk-update-akun', [\App\Http\Controllers\ImportKasController::class, 'bulkUpdateAkun'])->name('import-kas.bulk-update-akun');
        Route::post('import-kas/post', [\App\Http\Controllers\ImportKasController::class, 'post'])->name('import-kas.post');
        Route::delete('import-kas/clear', [\App\Http\Controllers\ImportKasController::class, 'clear'])->name('import-kas.clear');
    });
});
