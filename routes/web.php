<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CabangGedungController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\JabatanStatusController;
use App\Http\Controllers\LiburKhususController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CutiController;
use App\Http\Controllers\MesinController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\AbsensiPenggunaController;
use App\Http\Controllers\DendaController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\RekapSiswaController;

/*
|--------------------------------------------------------------------------
| Public / Machine Routes (ESP8266 RFID)
|--------------------------------------------------------------------------
*/
Route::match(['get', 'post'], '/pengguna/scan', [ScanController::class, 'index'])->name('pengguna.scan');
Route::match(['get', 'post'], '/absensi-machine', [AbsensiController::class, 'storeFromMachine']);

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Semua Role)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'hakAkses:superadmin,admin,general,orang_tua'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Libur Khusus (Bisa dilihat semua role)
    Route::get('/libur_khusus', [LiburKhususController::class, 'index'])->name('libur_khusus.index');
});

/*
|--------------------------------------------------------------------------
| Orang Tua Routes (Role: Orang Tua)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'hakAkses:orang_tua'])->group(function () {
    Route::get('/dashboard-ortu', [DashboardController::class, 'ortu'])->name('dashboard.ortu');
});

/*
|--------------------------------------------------------------------------
| Siswa / General User Routes (Role: General / Siswa)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'hakAkses:general,superadmin,admin'])->group(function () {
    Route::get('/absensi/pengguna', [AbsensiPenggunaController::class, 'rekapSaya'])->name('absensi.pengguna.saya');

    // Fitur web presensi & izin siswa
    Route::post('/presensi/siswa', [AbsensiController::class, 'storeSiswa'])->name('presensi-siswa.store');

    Route::get('/izin/siswa', [CutiController::class, 'izinSiswaIndex'])->name('siswa.izin.index');
    Route::get('/izin/siswa/create', [CutiController::class, 'izinSiswaCreate'])->name('siswa.izin.create');
    Route::post('/izin/siswa/store', [CutiController::class, 'izinSiswaStore'])->name('siswa.izin.store');

    Route::get('/absensi/rekap/pdf', [RekapSiswaController::class, 'exportPdf'])->name('siswa.rekap.pdf');
    Route::get('/absensi/rekap/excel', [RekapSiswaController::class, 'exportExcel'])->name('siswa.rekap.excel');
});

/*
|--------------------------------------------------------------------------
| Admin & SuperAdmin Routes (Role: SuperAdmin & Admin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'hakAkses:superadmin,admin'])->group(function () {

    // Absensi Admin
    Route::prefix('absensi')->name('absensi.')->group(function () {
        Route::get('/', [AbsensiController::class, 'index'])->name('index');
        Route::post('/', [AbsensiController::class, 'store'])->name('store');
        Route::get('/mesin', [AbsensiController::class, 'byMesin'])->name('mesin');
        Route::get('/pengguna/{nomor_induk}', [AbsensiPenggunaController::class, 'show'])->name('pengguna');
        Route::get('/{id}', [AbsensiController::class, 'show'])->name('show');
    });

    // Kelola Pengguna
    Route::prefix('pengguna')->name('pengguna.')->group(function () {
        Route::get('/', [PenggunaController::class, 'index'])->name('index');
        Route::post('/import', [PenggunaController::class, 'import'])->name('import'); // Import Massal Excel
        Route::get('/create', [PenggunaController::class, 'create'])->name('create');
        Route::post('/', [PenggunaController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [PenggunaController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PenggunaController::class, 'update'])->name('update');
        Route::delete('/{id}', [PenggunaController::class, 'destroy'])->name('destroy');
    });

    // Kelola Mesin
    Route::prefix('mesin')->name('mesin.')->group(function () {
        Route::get('/', [MesinController::class, 'index'])->name('index');
        Route::get('/create', [MesinController::class, 'create'])->name('create');
        Route::post('/', [MesinController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [MesinController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MesinController::class, 'update'])->name('update');
        Route::delete('/{id}', [MesinController::class, 'destroy'])->name('destroy'); // Rute Hapus Mesin
    });

    // Kelola Cuti & Persetujuan Izin Siswa
    Route::prefix('cuti')->name('cuti.')->group(function () {
        Route::get('/', [CutiController::class, 'index'])->name('index');
        Route::get('/create', [CutiController::class, 'create'])->name('create');
        Route::post('/', [CutiController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CutiController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CutiController::class, 'update'])->name('update');
        Route::delete('/{id}', [CutiController::class, 'destroy'])->name('destroy');

        Route::post('/{id}/setujui', [CutiController::class, 'setujui'])->name('setujui');
        Route::post('/{id}/tolak', [CutiController::class, 'tolak'])->name('tolak');
    });

    // Cabang dan Gedung
    Route::prefix('cabang-gedung')->name('cabang-gedung.')->group(function () {
        Route::get('/', [CabangGedungController::class, 'index'])->name('index');
        Route::get('/create', [CabangGedungController::class, 'create'])->name('create');
        Route::post('/', [CabangGedungController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [CabangGedungController::class, 'edit'])->name('edit');
        Route::put('/{id}', [CabangGedungController::class, 'update'])->name('update');
        Route::delete('/{id}', [CabangGedungController::class, 'destroy'])->name('destroy'); // Rute Hapus Permanen
        Route::patch('/{id}/toggle', [CabangGedungController::class, 'toggle'])->name('toggle'); // Rute Aktif/Nonaktif
    });

    // Libur Khusus (Create, Edit, Update, Delete khusus Admin)
    Route::prefix('libur_khusus')->name('libur_khusus.')->group(function () {
        Route::post('/', [LiburKhususController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [LiburKhususController::class, 'edit'])->name('edit');
        Route::put('/{id}', [LiburKhususController::class, 'update'])->name('update');
        Route::delete('/{id}', [LiburKhususController::class, 'destroy'])->name('destroy');
    });

    // Jabatan & Status
    Route::prefix('jabatan')->name('jabatan.')->group(function () {
        Route::get('/', [JabatanStatusController::class, 'index'])->name('index');
        Route::get('/create', [JabatanStatusController::class, 'create'])->name('create');
        Route::post('/', [JabatanStatusController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [JabatanStatusController::class, 'edit'])->name('edit');
        Route::put('/{id}', [JabatanStatusController::class, 'update'])->name('update');
        Route::delete('/{id}', [JabatanStatusController::class, 'destroy'])->name('destroy');
    });

    // Pengaturan Denda
    Route::prefix('pengaturan/denda')->name('denda.')->group(function () {
        Route::get('/', [DendaController::class, 'index'])->name('index');
        Route::get('/create', [DendaController::class, 'create'])->name('create');
        Route::post('/', [DendaController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [DendaController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DendaController::class, 'update'])->name('update');
        Route::delete('/{id}', [DendaController::class, 'destroy'])->name('destroy');
    });

    // Pengaturan Jam Absensi
    Route::get('pengaturan/jam-absensi', [AbsensiController::class, 'editJam'])->name('jam-absensi.edit');
    Route::post('pengaturan/jam-absensi', [AbsensiController::class, 'updateJam'])->name('jam-absensi.update');
});

require __DIR__.'/auth.php';