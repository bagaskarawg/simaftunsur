<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('beranda')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
    Route::view('beranda', 'beranda')->name('beranda');
});

// Rute demo untuk memverifikasi middleware `peran`. Hanya dipakai pada
// fase scaffolding RBAC; akan dihapus saat modul nyata mulai dibangun.
Route::middleware(['auth', 'peran:admin,wd3'])->get('/demo-peran', function () {
    return response()->json([
        'pesan' => 'OK',
        'peran' => auth()->user()->peran,
    ]);
})->name('demo.peran');
