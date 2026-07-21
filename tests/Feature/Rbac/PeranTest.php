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

it('membatasi izin Staf WD III sesuai konfigurasi', function () {
    $stafWd3 = Pengguna::factory()->stafWd3()->create();

    expect($stafWd3->punyaIzin('mahasiswa.kelola'))->toBeTrue();
    expect($stafWd3->punyaIzin('klasterisasi.jalankan'))->toBeTrue();
    expect($stafWd3->punyaIzin('prestasi.kelola'))->toBeFalse();
    expect($stafWd3->punyaIzin('laporan.ekspor'))->toBeFalse();
});

it('membatasi izin Staf Prodi sesuai konfigurasi', function () {
    $stafProdi = Pengguna::factory()->stafProdi()->create();

    expect($stafProdi->punyaIzin('prestasi.kelola'))->toBeTrue();
    expect($stafProdi->punyaIzin('kegiatan.kelola'))->toBeTrue();
    expect($stafProdi->punyaIzin('mahasiswa.kelola'))->toBeFalse();
    expect($stafProdi->punyaIzin('klasterisasi.lihat'))->toBeFalse();
});

it('mengaktifkan Gate Laravel untuk Staf WD III dan menolak kaprodi', function () {
    $stafWd3 = Pengguna::factory()->stafWd3()->create();
    $kaprodi = Pengguna::factory()->kaprodi()->create();

    expect($stafWd3->can('mahasiswa.kelola'))->toBeTrue();
    expect($kaprodi->can('mahasiswa.kelola'))->toBeFalse();
    expect($kaprodi->can('mahasiswa.lihat'))->toBeTrue();
});

it('middleware peran menolak non-admin pada rute admin (pengguna)', function () {
    $this->withoutVite();

    $this->actingAs(Pengguna::factory()->stafWd3()->create())
        ->get(route('pengguna.index'))->assertForbidden();

    $this->actingAs(Pengguna::factory()->wd3()->create())
        ->get(route('pengguna.index'))->assertForbidden();
});

it('middleware peran mengizinkan admin pada rute admin (pengguna)', function () {
    $this->withoutVite();

    $this->actingAs(Pengguna::factory()->admin()->create())
        ->get(route('pengguna.index'))->assertOk();
});
