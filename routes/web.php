<?php

use App\Http\Controllers\TemplateIpkController;
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

        // Halaman impor IPK massal — daftarkan SEBELUM /{mahasiswa}
        // supaya tidak ditangkap sebagai route model binding.
        Volt::route('/ipk/impor',  'mahasiswa.impor')->name('ipk.impor');
        Route::get('/ipk/template', [TemplateIpkController::class, 'unduh'])->name('ipk.template');

        Volt::route('/{mahasiswa}',      'mahasiswa.detail')->name('detail');
        Volt::route('/{mahasiswa}/ubah', 'mahasiswa.ubah')->name('ubah');
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
