<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->admin = Pengguna::factory()->create(['peran' => 'admin']);
});

it('hanya admin yang dapat mengakses rute manajemen pengguna', function () {
    $staf = Pengguna::factory()->create(['peran' => 'staf']);

    $this->actingAs($staf)->get(route('pengguna.index'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('pengguna.index'))->assertOk();
});

it('menambahkan pengguna baru lewat form modal', function () {
    $this->actingAs($this->admin);

    Volt::test('pengguna.index')
        ->call('bukaTambah')
        ->assertSet('modeForm', 'tambah')
        ->set('nama', 'Dosen Baru')
        ->set('nip', '199001012020121009')
        ->set('email', 'dosen.baru@ft.unsur.ac.id')
        ->set('kata_sandi', 'rahasia123')
        ->set('peran', 'dosen')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('modeForm', 'tutup');

    $baru = Pengguna::where('nip', '199001012020121009')->first();
    expect($baru)->not->toBeNull()
        ->and($baru->peran)->toBe('dosen')
        ->and(Hash::check('rahasia123', $baru->kata_sandi))->toBeTrue();
});

it('memvalidasi field wajib & kata sandi minimal 8 karakter saat menambah', function () {
    $this->actingAs($this->admin);

    Volt::test('pengguna.index')
        ->call('bukaTambah')
        ->set('nama', '')
        ->set('nip', '')
        ->set('kata_sandi', '123')
        ->call('simpan')
        ->assertHasErrors(['nama', 'nip', 'kata_sandi']);
});

it('mengubah pengguna tanpa wajib mengganti kata sandi', function () {
    $target = Pengguna::factory()->create([
        'nama' => 'Nama Lama', 'peran' => 'staf', 'kata_sandi' => Hash::make('sandilama123'),
    ]);

    $this->actingAs($this->admin);

    Volt::test('pengguna.index')
        ->call('bukaEdit', $target->id)
        ->assertSet('modeForm', 'edit')
        ->assertSet('nama', 'Nama Lama')
        ->set('nama', 'Nama Baru')
        ->set('peran', 'kaprodi')
        ->call('simpan')
        ->assertHasNoErrors();

    $target->refresh();
    expect($target->nama)->toBe('Nama Baru')
        ->and($target->peran)->toBe('kaprodi')
        ->and(Hash::check('sandilama123', $target->kata_sandi))->toBeTrue(); // sandi tak berubah
});

it('mencegah admin menghapus akunnya sendiri', function () {
    $this->actingAs($this->admin);

    Volt::test('pengguna.index')->call('hapus', $this->admin->id);

    expect(Pengguna::whereKey($this->admin->id)->exists())->toBeTrue();
});

it('menghapus pengguna lain', function () {
    $lain = Pengguna::factory()->create(['peran' => 'staf']);

    $this->actingAs($this->admin);

    Volt::test('pengguna.index')->call('hapus', $lain->id);

    expect(Pengguna::whereKey($lain->id)->exists())->toBeFalse();
});
