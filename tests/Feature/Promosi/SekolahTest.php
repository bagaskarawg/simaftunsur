<?php

use App\Models\Pengguna;
use App\Models\Sekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->pengelola = Pengguna::factory()->admin()->create();   // promosi.kelola (saat ini hanya admin)
    $this->pemantau = Pengguna::factory()->wd3()->create();      // promosi.lihat saja
    $this->tanpaIzin = Pengguna::factory()->kaprodi()->create(); // tanpa izin promosi
});

it('menolak akses master sekolah tanpa izin promosi.lihat', function () {
    $this->actingAs($this->tanpaIzin);
    Volt::test('promosi.sekolah')->assertForbidden();
});

it('menampilkan tombol kelola hanya bagi yang berizin promosi.kelola', function () {
    $this->actingAs($this->pemantau);
    Volt::test('promosi.sekolah')->assertOk()->assertDontSee('Tambah Sekolah');

    $this->actingAs($this->pengelola);
    Volt::test('promosi.sekolah')->assertOk()->assertSee('Tambah Sekolah');
});

it('menambahkan, mengubah, dan menghapus sekolah', function () {
    $this->actingAs($this->pengelola);

    Volt::test('promosi.sekolah')
        ->call('bukaTambah')
        ->set('nama', 'SMAN 1 Cianjur')
        ->set('jenjang', 'SMA')
        ->set('kota', 'Cianjur')
        ->call('simpan')
        ->assertHasNoErrors();

    $sekolah = Sekolah::first();
    expect($sekolah->nama)->toBe('SMAN 1 Cianjur');

    Volt::test('promosi.sekolah')
        ->call('bukaEdit', $sekolah->id)
        ->set('nama', 'SMAN 2 Cianjur')
        ->call('simpan')
        ->assertHasNoErrors();

    expect($sekolah->refresh()->nama)->toBe('SMAN 2 Cianjur');

    Volt::test('promosi.sekolah')->call('hapus', $sekolah->id);
    expect(Sekolah::count())->toBe(0);
});
