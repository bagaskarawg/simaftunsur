<?php

use App\Models\KlasterisasiKategori;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->pengelola = Pengguna::factory()->wd3()->create();        // kategori-klaster.kelola + lihat
    $this->tanpaIzin = Pengguna::factory()->stafProdi()->create();  // tanpa izin kategori-klaster
});

it('menolak akses tanpa izin kategori-klaster.lihat', function () {
    $this->actingAs($this->tanpaIzin);
    Volt::test('kategori-klaster.index')->assertForbidden();
});

it('menampilkan tombol kelola hanya bagi yang berizin', function () {
    $this->actingAs($this->pengelola);
    Volt::test('kategori-klaster.index')->assertOk()->assertSee('Tambah Kategori');
});

it('menambahkan kategori klaster lewat modal', function () {
    $this->actingAs($this->pengelola);

    Volt::test('kategori-klaster.index')
        ->call('bukaTambah')
        ->assertSet('modeForm', 'tambah')
        ->set('nama', 'Berprestasi')
        ->set('urutan', 1)
        ->set('deskripsi', 'Skor komposit tertinggi.')
        ->set('rekomendasi', 'Dorong lomba nasional.')
        ->set('warna', 'cluster-1')
        ->set('aktif', true)
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('modeForm', 'tutup');

    $kat = KlasterisasiKategori::first();
    expect($kat)->not->toBeNull()
        ->and($kat->nama)->toBe('Berprestasi')
        ->and($kat->urutan)->toBe(1)
        ->and($kat->aktif)->toBeTrue();
});

it('memvalidasi nama wajib & unik', function () {
    KlasterisasiKategori::factory()->create(['nama' => 'Menengah']);
    $this->actingAs($this->pengelola);

    Volt::test('kategori-klaster.index')
        ->call('bukaTambah')
        ->set('nama', '')
        ->call('simpan')
        ->assertHasErrors(['nama' => 'required']);

    Volt::test('kategori-klaster.index')
        ->call('bukaTambah')
        ->set('nama', 'Menengah')
        ->call('simpan')
        ->assertHasErrors(['nama' => 'unique']);
});

it('mengubah dan menghapus kategori klaster', function () {
    $kat = KlasterisasiKategori::factory()->create(['nama' => 'Lama', 'urutan' => 2]);
    $this->actingAs($this->pengelola);

    Volt::test('kategori-klaster.index')
        ->call('bukaEdit', $kat->id)
        ->assertSet('nama', 'Lama')
        ->set('nama', 'Baru')
        ->set('rekomendasi', 'Rekomendasi baru.')
        ->call('simpan')
        ->assertHasNoErrors();

    $kat->refresh();
    expect($kat->nama)->toBe('Baru')->and($kat->rekomendasi)->toBe('Rekomendasi baru.');

    Volt::test('kategori-klaster.index')->call('hapus', $kat->id);
    expect(KlasterisasiKategori::whereKey($kat->id)->exists())->toBeFalse();
});

it('kategori baru disisipkan sebagai level tengah, anchor terbawah tetap terakhir', function () {
    // Katalog awal: Berprestasi(1), Menengah(2), Perlu Bimbingan(3).
    KlasterisasiKategori::factory()->create(['nama' => 'Berprestasi', 'urutan' => 1, 'aktif' => true]);
    KlasterisasiKategori::factory()->create(['nama' => 'Menengah', 'urutan' => 2, 'aktif' => true]);
    KlasterisasiKategori::factory()->create(['nama' => 'Perlu Bimbingan', 'urutan' => 3, 'aktif' => true]);

    $this->actingAs($this->pengelola);

    // Tambah "Percobaan" tanpa mengubah urutan default (harus jadi level tengah).
    $komponen = Volt::test('kategori-klaster.index')->call('bukaTambah');
    $komponen->assertSet('urutan', 3) // default = tepat sebelum anchor terbawah
        ->set('nama', 'Percobaan')
        ->call('simpan')
        ->assertHasNoErrors();

    // Perlu Bimbingan tetap entri TERAKHIR (urutan terbesar) → tetap jadi label
    // klaster terendah untuk k berapa pun.
    expect(KlasterisasiKategori::katalogAktif())
        ->toBe(['Berprestasi', 'Menengah', 'Percobaan', 'Perlu Bimbingan']);

    expect(KlasterisasiKategori::where('nama', 'Perlu Bimbingan')->value('urutan'))->toBe(4);
});

it('katalogAktif mengembalikan nama terurut peringkat', function () {
    KlasterisasiKategori::factory()->create(['nama' => 'Perlu Bimbingan', 'urutan' => 3, 'aktif' => true]);
    KlasterisasiKategori::factory()->create(['nama' => 'Berprestasi', 'urutan' => 1, 'aktif' => true]);
    KlasterisasiKategori::factory()->create(['nama' => 'Menengah', 'urutan' => 2, 'aktif' => true]);
    KlasterisasiKategori::factory()->create(['nama' => 'Nonaktif', 'urutan' => 1, 'aktif' => false]);

    expect(KlasterisasiKategori::katalogAktif())
        ->toBe(['Berprestasi', 'Menengah', 'Perlu Bimbingan']);
});
