<?php

use App\Models\Mahasiswa;
use App\Models\Pengguna;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->mahasiswa = Mahasiswa::factory()->untukProdi($this->prodi)->create();
    $this->stafProdi = Pengguna::factory()->stafProdi()->create(); // prestasi.kelola
    $this->pemantau = Pengguna::factory()->wd3()->create();        // prestasi.lihat saja
});

it('menampilkan tombol kelola hanya untuk yang berizin prestasi.kelola', function () {
    $this->actingAs($this->pemantau);
    Volt::test('prestasi.index')->assertOk()->assertDontSee('Tambah Prestasi');

    $this->actingAs($this->stafProdi);
    Volt::test('prestasi.index')->assertOk()->assertSee('Tambah Prestasi');
});

it('menolak aksi kelola dari pengguna yang hanya berizin lihat', function () {
    $this->actingAs($this->pemantau);

    Volt::test('prestasi.index')->call('bukaTambah')->assertForbidden();
});

it('menambahkan prestasi lewat form modal', function () {
    $this->actingAs($this->stafProdi);

    Volt::test('prestasi.index')
        ->call('bukaTambah')
        ->assertSet('modeForm', 'tambah')
        ->set('mahasiswa_id', $this->mahasiswa->id)
        ->set('judul', 'Juara 1 LKTI Nasional')
        ->set('jenis', 'akademik')
        ->set('tingkat', 'nasional')
        ->set('peringkat', 'Juara 1')
        ->set('tanggal', '2026-03-15')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('modeForm', 'tutup');

    $prestasi = Prestasi::first();
    expect($prestasi)->not->toBeNull()
        ->and($prestasi->mahasiswa_id)->toBe($this->mahasiswa->id)
        ->and($prestasi->tingkat)->toBe('nasional');
});

it('mengunggah berkas bukti saat menambah prestasi', function () {
    Storage::fake('public');
    $this->actingAs($this->stafProdi);

    Volt::test('prestasi.index')
        ->call('bukaTambah')
        ->set('mahasiswa_id', $this->mahasiswa->id)
        ->set('judul', 'Juara dengan bukti')
        ->set('tingkat', 'nasional')
        ->set('berkas', UploadedFile::fake()->create('sertifikat.pdf', 100, 'application/pdf'))
        ->call('simpan')
        ->assertHasNoErrors();

    $prestasi = Prestasi::first();
    expect($prestasi->berkas_bukti)->not->toBeNull();
    Storage::disk('public')->assertExists($prestasi->berkas_bukti);
});

it('memvalidasi field wajib saat menambah prestasi', function () {
    $this->actingAs($this->stafProdi);

    Volt::test('prestasi.index')
        ->call('bukaTambah')
        ->set('mahasiswa_id', null)
        ->set('judul', '')
        ->set('url_bukti', 'bukan-url')
        ->call('simpan')
        ->assertHasErrors(['mahasiswa_id', 'judul', 'url_bukti']);
});

it('mengubah dan menghapus prestasi', function () {
    $prestasi = Prestasi::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'judul' => 'Judul Lama', 'tingkat' => 'lokal',
    ]);

    $this->actingAs($this->stafProdi);

    Volt::test('prestasi.index')
        ->call('bukaEdit', $prestasi->id)
        ->assertSet('judul', 'Judul Lama')
        ->set('judul', 'Judul Baru')
        ->set('tingkat', 'internasional')
        ->call('simpan')
        ->assertHasNoErrors();

    $prestasi->refresh();
    expect($prestasi->judul)->toBe('Judul Baru')->and($prestasi->tingkat)->toBe('internasional');

    Volt::test('prestasi.index')->call('hapus', $prestasi->id);
    expect(Prestasi::whereKey($prestasi->id)->exists())->toBeFalse();
});
