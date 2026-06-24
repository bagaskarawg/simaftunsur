<?php

use App\Http\Controllers\LaporanController;
use App\Http\Controllers\TemplateIpkController;
use App\Http\Controllers\TemplateMahasiswaController;
use App\Http\Controllers\TemplatePenggunaController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('beranda')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::view('beranda', 'beranda')->name('beranda');

    // Profil akun sendiri (semua pengguna terautentikasi).
    Volt::route('profil', 'profil.index')->name('profil');

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
        Volt::route('/impor', 'pengguna.impor')->name('impor');
        Route::get('/template', [TemplatePenggunaController::class, 'unduh'])->name('template');
    });

    // Pengaturan Sistem — konfigurasi ringan, khusus Administrator.
    Route::prefix('pengaturan')->name('pengaturan.')->middleware('peran:admin')->group(function () {
        Volt::route('/', 'pengaturan.index')->name('index');
    });

    // Log Aktivitas — jejak audit, khusus Administrator.
    Route::prefix('log-aktivitas')->name('log-aktivitas.')->middleware('peran:admin')->group(function () {
        Volt::route('/', 'log-aktivitas.index')->name('index');
    });

    // Modul Prestasi — CRUD pendukung. Otorisasi (lihat vs kelola) di komponen.
    Route::prefix('prestasi')->name('prestasi.')->group(function () {
        Volt::route('/', 'prestasi.index')->name('index');
    });

    // Modul Laporan — rekap kemahasiswaan (read-only) + ekspor CSV.
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Volt::route('/', 'laporan.index')->name('index');
        Route::get('/ekspor/prodi', [LaporanController::class, 'eksporProdi'])->name('ekspor.prodi');
        Route::get('/ekspor/pdf', [LaporanController::class, 'eksporPdf'])->name('ekspor.pdf');
    });

    // Modul Tracer Study — CRUD pendukung. Otorisasi (lihat vs kelola) di komponen.
    Route::prefix('tracer')->name('tracer.')->group(function () {
        Volt::route('/', 'tracer.index')->name('index');
    });

    // Modul Promosi/PMB — CRUD pendukung (versi ringkas: kegiatan promosi).
    Route::prefix('promosi')->name('promosi.')->group(function () {
        Volt::route('/', 'promosi.index')->name('index');
    });
});
