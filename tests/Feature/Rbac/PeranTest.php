<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('memberi admin akses penuh lewat wildcard', function () {
    $admin = Pengguna::factory()->admin()->create();

    expect($admin->punyaIzin('mahasiswa.kelola'))->toBeTrue();
    expect($admin->punyaIzin('klasterisasi.jalankan'))->toBeTrue();
    expect($admin->punyaIzin('izin.fiktif.yang.belum.ada'))->toBeTrue();
});

it('membatasi izin staf sesuai konfigurasi', function () {
    $staf = Pengguna::factory()->create(['peran' => 'staf']);

    expect($staf->punyaIzin('mahasiswa.kelola'))->toBeTrue();
    expect($staf->punyaIzin('prestasi.kelola'))->toBeTrue();
    expect($staf->punyaIzin('klasterisasi.jalankan'))->toBeFalse();
    expect($staf->punyaIzin('laporan.ekspor'))->toBeFalse();
});

it('mengaktifkan Gate Laravel untuk staf dan menolak dosen', function () {
    $staf = Pengguna::factory()->create(['peran' => 'staf']);
    $dosen = Pengguna::factory()->create(['peran' => 'dosen']);

    expect($staf->can('mahasiswa.kelola'))->toBeTrue();
    expect($dosen->can('mahasiswa.kelola'))->toBeFalse();
    expect($dosen->can('mahasiswa.lihat'))->toBeTrue();
});

it('middleware peran menolak non-admin pada rute admin (pengguna)', function () {
    $this->withoutVite();

    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf']))
        ->get(route('pengguna.index'))->assertForbidden();

    $this->actingAs(Pengguna::factory()->wd3()->create())
        ->get(route('pengguna.index'))->assertForbidden();
});

it('middleware peran mengizinkan admin pada rute admin (pengguna)', function () {
    $this->withoutVite();

    $this->actingAs(Pengguna::factory()->admin()->create())
        ->get(route('pengguna.index'))->assertOk();
});
