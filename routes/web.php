<?php

use App\Http\Controllers\TemplateIpkController;
use App\Http\Controllers\TemplateMahasiswaController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('beranda')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::view('beranda', 'beranda')->name('beranda');

    // Modul Data Mahasiswa — dapat diakses oleh seluruh peran yang
    // setidaknya berizin `mahasiswa.lihat`. Otorisasi finer-grained
    // (kelola vs lihat) dilakukan di dalam Volt component lewat Gate.
    Route::prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Volt::route('/',           'mahasiswa.index')->name('index');
        Volt::route('/baru',       'mahasiswa.baru')->name('baru');

        // Halaman impor massal — daftarkan SEBELUM /{mahasiswa}
        // supaya tidak ditangkap sebagai route model binding.
        Volt::route('/ipk/impor',  'mahasiswa.impor')->name('ipk.impor');
        Route::get('/ipk/template', [TemplateIpkController::class, 'unduh'])->name('ipk.template');

        Volt::route('/impor',  'mahasiswa.impor-data')->name('impor');
        Route::get('/template', [TemplateMahasiswaController::class, 'unduh'])->name('template');

        Volt::route('/{mahasiswa}',      'mahasiswa.detail')->name('detail');
        Volt::route('/{mahasiswa}/ubah', 'mahasiswa.ubah')->name('ubah');
    });

    // Modul Klasterisasi K-Means — dashboard hasil + tombol jalankan.
    // Otorisasi finer-grained (lihat vs jalankan) ditangani di dalam komponen.
    Route::prefix('klasterisasi')->name('klasterisasi.')->group(function () {
        Volt::route('/', 'klasterisasi.index')->name('index');
    });

    // Modul Pengguna — manajemen akun & peran, khusus Administrator.
    Route::prefix('pengguna')->name('pengguna.')->middleware('peran:admin')->group(function () {
        Volt::route('/', 'pengguna.index')->name('index');
    });

    // Modul Prestasi — CRUD pendukung. Otorisasi (lihat vs kelola) di komponen.
    Route::prefix('prestasi')->name('prestasi.')->group(function () {
        Volt::route('/', 'prestasi.index')->name('index');
    });
});

// Rute demo untuk memverifikasi middleware `peran`. Hanya dipakai pada
// fase scaffolding RBAC; akan dihapus saat modul nyata mulai dibangun.
Route::middleware(['auth', 'peran:admin,wd3'])->get('/demo-peran', function () {
    return response()->json([
        'pesan' => 'OK',
        'peran' => auth()->user()->peran,
    ]);
})->name('demo.peran');
