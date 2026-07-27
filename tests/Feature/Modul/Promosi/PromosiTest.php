<?php

use App\Models\KegiatanPromosi;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->pengelola = Pengguna::factory()->admin()->create();   // promosi.kelola (saat ini hanya admin)
    $this->pemantau = Pengguna::factory()->wd3()->create();      // promosi.lihat saja
    $this->tanpaIzin = Pengguna::factory()->kaprodi()->create(); // tanpa izin promosi
});

it('menolak akses tanpa izin promosi.lihat', function () {
    $this->actingAs($this->tanpaIzin);
    Volt::test('promosi.index')->assertForbidden();
});

it('menampilkan tombol kelola hanya untuk yang berizin promosi.kelola', function () {
    $this->actingAs($this->pemantau);
    Volt::test('promosi.index')->assertOk()->assertDontSee('Tambah Kegiatan');

    $this->actingAs($this->pengelola);
    Volt::test('promosi.index')->assertOk()->assertSee('Tambah Kegiatan');
});

it('menambahkan kegiatan promosi lewat form modal', function () {
    $this->actingAs($this->pengelola);

    Volt::test('promosi.index')
        ->call('bukaTambah')
        ->set('nama_kegiatan', 'Sosialisasi PMB 2026')
        ->set('sekolah_target', 'SMAN 1 Cianjur')
        ->set('kota', 'Cianjur')
        ->set('tanggal', '2026-05-10')
        ->set('jumlah_peminat', 45)
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('modeForm', 'tutup');

    $k = KegiatanPromosi::first();
    expect($k)->not->toBeNull()
        ->and($k->sekolah_target)->toBe('SMAN 1 Cianjur')
        ->and($k->jumlah_peminat)->toBe(45);
});

it('memvalidasi field wajib saat menambah kegiatan', function () {
    $this->actingAs($this->pengelola);

    Volt::test('promosi.index')
        ->call('bukaTambah')
        ->set('nama_kegiatan', '')
        ->set('sekolah_target', '')
        ->call('simpan')
        ->assertHasErrors(['nama_kegiatan', 'sekolah_target']);
});

it('mengubah dan menghapus kegiatan promosi', function () {
    $k = KegiatanPromosi::factory()->create(['nama_kegiatan' => 'Lama']);

    $this->actingAs($this->pengelola);

    Volt::test('promosi.index')
        ->call('bukaEdit', $k->id)
        ->set('nama_kegiatan', 'Baru')
        ->call('simpan')
        ->assertHasNoErrors();

    expect($k->refresh()->nama_kegiatan)->toBe('Baru');

    Volt::test('promosi.index')->call('hapus', $k->id);
    expect(KegiatanPromosi::whereKey($k->id)->exists())->toBeFalse();
});
