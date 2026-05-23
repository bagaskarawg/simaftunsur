<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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
    expect($staf->punyaIzin('ipk.kelola'))->toBeTrue();
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

it('memberi 403 pada staf saat mengakses rute beralias peran admin/wd3', function () {
    $staf = Pengguna::factory()->create([
        'peran'      => 'staf',
        'kata_sandi' => Hash::make('rahasia123'),
    ]);

    $respons = $this->actingAs($staf)->get('/demo-peran');

    $respons->assertForbidden();
});

it('mengizinkan WD III mengakses rute beralias peran admin/wd3', function () {
    $wd3 = Pengguna::factory()->wd3()->create([
        'kata_sandi' => Hash::make('rahasia123'),
    ]);

    $respons = $this->actingAs($wd3)->get('/demo-peran');

    $respons->assertOk();
    $respons->assertJson(['pesan' => 'OK', 'peran' => 'wd3']);
});
