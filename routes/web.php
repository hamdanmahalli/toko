<?php

use App\Http\Controllers\AbsenController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PengajuanController as AdminPengajuanController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\ShiftTemplateController;
use App\Http\Controllers\Admin\ShiftWindowController;
use App\Http\Controllers\Admin\ShopController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KlaimController;
use App\Http\Controllers\PanduanController;
use App\Http\Controllers\PengajuanController;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/beranda')->name('awal');

// Panduan dibiarkan terbuka tanpa login supaya orang yang sedang lupa
// password masih bisa membacanya.
Route::get('/panduan', [PanduanController::class, 'index'])->name('panduan');

// Perangkat presensi karyawan untuk karyawan yang tidak memakai HP. Tidak
// memakai akun karyawan: satu user dan satu password per toko dipakai
// bersama, jadi orang pertama yang datang bisa langsung memindai kartunya.
// Di balik login itu, yang membatasi tetap kartu: hanya kartu yang berlaku
// milik toko itu yang bisa dicatat, dan setiap percobaan masuk audit log.
//
// Batas tipis pada pemindaian, bukan throttle, dipilih supaya operator yang
// salah ketik kartu tidak terkunci keluar di tengah shift. Login memakai
// throttle lebih ketat karena menebak password harus dihentikan.
Route::get('/presensi/{kode}', [PresensiController::class, 'form'])
    ->where('kode', '[A-Za-z0-9_-]+')
    ->middleware('throttle:120,1')
    ->name('presensi.form');
Route::post('/presensi/{kode}/masuk', [PresensiController::class, 'prosesMasuk'])
    ->where('kode', '[A-Za-z0-9_-]+')
    ->middleware('throttle:10,1')
    ->name('presensi.masuk');
Route::post('/presensi/{kode}/keluar', [PresensiController::class, 'keluar'])
    ->where('kode', '[A-Za-z0-9_-]+')
    ->name('presensi.keluar');
Route::post('/presensi/{kode}', [PresensiController::class, 'proses'])
    ->where('kode', '[A-Za-z0-9_-]+')
    ->middleware('throttle:30,1')
    ->name('presensi.proses');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'formMasuk'])->name('masuk');
    Route::post('/masuk', [AuthController::class, 'masuk'])->middleware('throttle:10,1');

    // Klaim akun mandiri: NIP + email di data karyawan, pilih username sendiri.
    Route::get('/klaim', [KlaimController::class, 'form'])->name('klaim.form');
    Route::post('/klaim', [KlaimController::class, 'klaim'])
        ->middleware('throttle:5,1')
        ->name('klaim.proses');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'keluar'])->name('keluar');

    // Kelola akun sendiri: username, password, dan izin perangkat milik sendiri.
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
    Route::post('/profil/username', [ProfilController::class, 'gantiUsername'])->name('profil.username');
    Route::post('/profil/password', [ProfilController::class, 'gantiPassword'])->name('profil.password');
    Route::post('/profil/perangkat/{perangkat}/cabut', [ProfilController::class, 'cabutPerangkat'])
        ->name('profil.perangkat-cabut');

    Route::get('/beranda', [AbsenController::class, 'beranda'])
        ->middleware('permission:dashboard.lihat')
        ->name('beranda');

    // Membaca riwayat dan kartu sendiri tidak perlu hak mencatat. Karmanya
    // dipisah supaya akun yang hanya boleh melihat (mis. yang sudah dinonaktifkan
    // dari mesin absen tapi masih boleh cek riwayat) tidak ikut kehilangan
    // akses ke data miliknya sendiri.
    Route::middleware('permission:absen.lihat')->group(function () {
        Route::get('/saya-qr', [AbsenController::class, 'kartuQr'])->name('absen.qr');
        Route::get('/riwayat', [AbsenController::class, 'riwayat'])->name('absen.riwayat');
    });

    Route::middleware('permission:absen.catat')->group(function () {
        Route::post('/absen', [AbsenController::class, 'catat'])->name('absen.catat');
    });

    // Pengajuan dibuat karyawan sendiri, persetujuannya di modul admin.
    Route::middleware('permission:pengajuan.lihat')->group(function () {
        Route::get('/pengajuan', [PengajuanController::class, 'index'])->name('pengajuan.index');
        Route::get('/pengajuan/izin', [PengajuanController::class, 'formIzin'])->name('pengajuan.izin');
        Route::get('/pengajuan/lembur', [PengajuanController::class, 'formLembur'])->name('pengajuan.lembur');
    });

    Route::middleware('permission:pengajuan.buat')->group(function () {
        Route::post('/pengajuan/izin', [PengajuanController::class, 'simpanIzin'])->name('pengajuan.izin.store');
        Route::post('/pengajuan/lembur', [PengajuanController::class, 'simpanLembur'])->name('pengajuan.lembur.store');
        Route::post('/pengajuan/{jenis}/{id}/batal', [PengajuanController::class, 'batal'])
            ->whereIn('jenis', ['izin', 'lembur'])
            ->name('pengajuan.batal');
    });

    // ---------------------------------------------------------------- admin
    // `tolak_karyawan` dipasang di luar group permission supaya akun karyawan
    // yang kebetulan memegang permission admin ditolak dengan pesan yang jelas,
    // bukan 403 generik dari Spatie.
    Route::prefix('admin')->name('admin.')->middleware('tolak_karyawan')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])
            ->middleware('permission:dashboard.lihat')
            ->name('dashboard');

        Route::middleware('permission:toko.lihat')->group(function () {
            Route::get('/toko', [ShopController::class, 'index'])->name('toko.index');
        });

        Route::middleware('permission:toko.kelola')->group(function () {
            Route::get('/toko/tambah', [ShopController::class, 'create'])->name('toko.create');
            Route::post('/toko', [ShopController::class, 'store'])->name('toko.store');
            Route::get('/toko/{toko}/ubah', [ShopController::class, 'edit'])->name('toko.edit');
            Route::put('/toko/{toko}', [ShopController::class, 'update'])->name('toko.update');
            Route::delete('/toko/{toko}', [ShopController::class, 'destroy'])->name('toko.destroy');
        });

        Route::middleware('permission:karyawan.lihat')->group(function () {
            Route::get('/karyawan', [EmployeeController::class, 'index'])->name('karyawan.index');
            Route::get('/karyawan/{karyawan}/qr', [EmployeeController::class, 'qr'])->name('karyawan.qr');
        });

        Route::middleware('permission:karyawan.kelola')->group(function () {
            Route::get('/karyawan/tambah', [EmployeeController::class, 'create'])->name('karyawan.create');
            Route::post('/karyawan', [EmployeeController::class, 'store'])->name('karyawan.store');
            Route::get('/karyawan/{karyawan}/ubah', [EmployeeController::class, 'edit'])->name('karyawan.edit');
            Route::put('/karyawan/{karyawan}', [EmployeeController::class, 'update'])->name('karyawan.update');
            Route::post('/karyawan/{karyawan}/password', [EmployeeController::class, 'resetPassword'])
                ->name('karyawan.reset-password');
            Route::post('/karyawan/{karyawan}/rotasi-qr', [EmployeeController::class, 'rotasiQr'])
                ->name('karyawan.rotasi-qr');
        });

        Route::middleware('permission:jabatan.lihat')->group(function () {
            Route::get('/jabatan', [PositionController::class, 'index'])->name('jabatan.index');
        });

        Route::middleware('permission:jabatan.kelola')->group(function () {
            Route::get('/jabatan/tambah', [PositionController::class, 'create'])->name('jabatan.create');
            Route::post('/jabatan', [PositionController::class, 'store'])->name('jabatan.store');
            Route::get('/jabatan/{jabatan}/ubah', [PositionController::class, 'edit'])->name('jabatan.edit');
            Route::put('/jabatan/{jabatan}', [PositionController::class, 'update'])->name('jabatan.update');
            Route::delete('/jabatan/{jabatan}', [PositionController::class, 'destroy'])->name('jabatan.destroy');
        });

        Route::middleware('permission:shift.lihat')->group(function () {
            Route::get('/shift', [ShiftTemplateController::class, 'index'])->name('shift.index');
        });

        Route::middleware('permission:shift.kelola')->group(function () {
            Route::get('/shift/tambah', [ShiftTemplateController::class, 'create'])->name('shift.create');
            Route::post('/shift', [ShiftTemplateController::class, 'store'])->name('shift.store');
            Route::get('/shift/{template}/ubah', [ShiftTemplateController::class, 'edit'])->name('shift.edit');
            Route::put('/shift/{template}', [ShiftTemplateController::class, 'update'])->name('shift.update');
            Route::delete('/shift/{template}', [ShiftTemplateController::class, 'destroy'])->name('shift.destroy');
        });

        // Pita waktu shift (Pagi/Siang) untuk melabeli hasil absensi.
        Route::middleware('permission:shift.lihat')->group(function () {
            Route::get('/window', [ShiftWindowController::class, 'index'])->name('window.index');
        });

        Route::middleware('permission:shift.kelola')->group(function () {
            Route::post('/window', [ShiftWindowController::class, 'store'])->name('window.store');
            Route::put('/window/{window}', [ShiftWindowController::class, 'update'])->name('window.update');
            Route::delete('/window/{window}', [ShiftWindowController::class, 'destroy'])->name('window.destroy');
            Route::post('/window/{window}/aktifkan', [ShiftWindowController::class, 'aktifkan'])->name('window.aktifkan');
        });

        Route::middleware('permission:laporan.lihat')->group(function () {
            Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        });

        Route::middleware('permission:pengajuan.lihat')->group(function () {
            Route::get('/pengajuan', [AdminPengajuanController::class, 'index'])->name('pengajuan.index');
        });

        Route::middleware('permission:pengajuan.setujui')->group(function () {
            Route::post('/pengajuan/{jenis}/{id}/setujui', [AdminPengajuanController::class, 'setujui'])
                ->whereIn('jenis', ['izin', 'lembur'])
                ->name('pengajuan.setujui');
            Route::post('/pengajuan/{jenis}/{id}/tolak', [AdminPengajuanController::class, 'tolak'])
                ->whereIn('jenis', ['izin', 'lembur'])
                ->name('pengajuan.tolak');
        });

        // Daftar akun dan penugasan tokonya. Menulis peran dan toko
        // butuh `pengguna.kelola`, yang tidak diberikan ke supervisor.
        Route::middleware('permission:pengguna.lihat')->group(function () {
            Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');

            Route::put('/pengguna/{pengguna}', [PenggunaController::class, 'update'])
                ->middleware('permission:pengguna.kelola')
                ->name('pengguna.update');
        });

        // Persetujuan perangkat terpisah dari `pengguna.kelola`: supervisor
        // kepala toko boleh memberi izin login perangkat karyawannya sendiri.
        Route::middleware('permission:perangkat.kelola')->group(function () {
            Route::post('/pengguna/{pengguna}/perangkat/{perangkat}/setujui', [PenggunaController::class, 'perangkatSetujui'])
                ->name('pengguna.perangkat-setujui');
            Route::post('/pengguna/{pengguna}/perangkat/{perangkat}/tolak', [PenggunaController::class, 'perangkatTolak'])
                ->name('pengguna.perangkat-tolak');
            Route::delete('/pengguna/{pengguna}/perangkat/{perangkat}', [PenggunaController::class, 'perangkatHapus'])
                ->name('pengguna.perangkat-hapus');
        });

        // Menonaktifkan bukan menghapus: riwayat absensi wajib aman.
        // `karyawan.hapus` disimpan untuk penghapusan permanen di masa depan.
        Route::delete('/karyawan/{karyawan}', [EmployeeController::class, 'destroy'])
            ->middleware('permission:karyawan.kelola')
            ->name('karyawan.destroy');
    });
});
