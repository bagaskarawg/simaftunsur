<?php

use App\Models\Mahasiswa;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->prodi = ProgramStudi::create([
        'kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1',
    ]);
    $this->mahasiswa = Mahasiswa::factory()->untukProdi($this->prodi)->create();
});

it('menampilkan form tambah IPK di dalam modal (bukan inline) saat dibuka', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf']));

    $komponen = Volt::test('mahasiswa.detail', ['mahasiswa' => $this->mahasiswa]);

    // Sebelum dibuka: form tidak terlihat.
    $komponen->assertDontSee('Tahun Akademik');

    // Membuka panel "manual" mengubah state → modal tampil.
    $komponen->call('bukaPanel', 'manual')
        ->assertSet('modePanel', 'manual')
        ->assertSee('Tambah IPK Semester')
        ->assertSee('Tahun Akademik')
        ->assertSeeHtml('aria-modal="true"'); // ditandai sebagai dialog/modal
});

it('menutup modal mengembalikan modePanel ke tutup', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf']));

    Volt::test('mahasiswa.detail', ['mahasiswa' => $this->mahasiswa])
        ->call('bukaPanel', 'impor')
        ->assertSet('modePanel', 'impor')
        ->assertSeeHtml('aria-modal="true"')
        ->call('tutupPanel')
        ->assertSet('modePanel', 'tutup')
        ->assertDontSeeHtml('aria-modal="true"');
});
