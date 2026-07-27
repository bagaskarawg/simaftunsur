<?php

use App\Models\Mahasiswa;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use App\Models\TracerStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->mahasiswa = Mahasiswa::factory()->untukProdi($this->prodi)->create();
    $this->pengelola = Pengguna::factory()->admin()->create();   // tracer.kelola (saat ini hanya admin)
    $this->pemantau = Pengguna::factory()->wd3()->create();      // tracer.lihat saja
    $this->tanpaIzin = Pengguna::factory()->kaprodi()->create(); // tanpa izin tracer
});

it('menolak akses tanpa izin tracer.lihat', function () {
    $this->actingAs($this->tanpaIzin);
    Volt::test('tracer.index')->assertForbidden();
});

it('menampilkan tombol kelola hanya untuk yang berizin tracer.kelola', function () {
    $this->actingAs($this->pemantau);
    Volt::test('tracer.index')->assertOk()->assertDontSee('Tambah Data Tracer');

    $this->actingAs($this->pengelola);
    Volt::test('tracer.index')->assertOk()->assertSee('Tambah Data Tracer');
});

it('menolak aksi kelola dari pengguna yang hanya berizin lihat', function () {
    $this->actingAs($this->pemantau);
    Volt::test('tracer.index')->call('bukaTambah')->assertForbidden();
});

it('menambahkan data tracer lewat form modal', function () {
    $this->actingAs($this->pengelola);

    Volt::test('tracer.index')
        ->call('bukaTambah')
        ->set('mahasiswa_id', $this->mahasiswa->id)
        ->set('tahun_lulus', 2024)
        ->set('status_pekerjaan', 'bekerja')
        ->set('nama_instansi', 'PT Maju Jaya')
        ->set('relevansi', 'relevan')
        ->set('masa_tunggu_bulan', 6)
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('modeForm', 'tutup');

    $tracer = TracerStudy::first();
    expect($tracer)->not->toBeNull()
        ->and($tracer->mahasiswa_id)->toBe($this->mahasiswa->id)
        ->and($tracer->status_pekerjaan)->toBe('bekerja')
        ->and($tracer->masa_tunggu_bulan)->toBe(6);
});

it('memvalidasi field wajib saat menambah tracer', function () {
    $this->actingAs($this->pengelola);

    Volt::test('tracer.index')
        ->call('bukaTambah')
        ->set('mahasiswa_id', null)
        ->set('status_pekerjaan', 'tidak_valid')
        ->set('masa_tunggu_bulan', 999)
        ->call('simpan')
        ->assertHasErrors(['mahasiswa_id', 'status_pekerjaan', 'masa_tunggu_bulan']);
});

it('mengubah dan menghapus data tracer', function () {
    $tracer = TracerStudy::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'status_pekerjaan' => 'belum_bekerja',
    ]);

    $this->actingAs($this->pengelola);

    Volt::test('tracer.index')
        ->call('bukaEdit', $tracer->id)
        ->set('status_pekerjaan', 'wirausaha')
        ->call('simpan')
        ->assertHasNoErrors();

    expect($tracer->refresh()->status_pekerjaan)->toBe('wirausaha');

    Volt::test('tracer.index')->call('hapus', $tracer->id);
    expect(TracerStudy::whereKey($tracer->id)->exists())->toBeFalse();
});
