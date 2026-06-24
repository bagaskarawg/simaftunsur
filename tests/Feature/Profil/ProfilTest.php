<?php

use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('memperbarui nama & email profil sendiri', function () {
    $pengguna = Pengguna::factory()->create(['peran' => 'staf', 'nama' => 'Nama Lama']);
    $this->actingAs($pengguna);

    Volt::test('profil.index')
        ->set('nama', 'Nama Baru')
        ->set('email', 'baru@ft.unsur.ac.id')
        ->call('simpanProfil')
        ->assertHasNoErrors();

    $pengguna->refresh();
    expect($pengguna->nama)->toBe('Nama Baru')
        ->and($pengguna->email)->toBe('baru@ft.unsur.ac.id');
});

it('mengubah kata sandi dengan kata sandi lama yang benar', function () {
    $pengguna = Pengguna::factory()->create([
        'peran' => 'staf', 'kata_sandi' => Hash::make('lamasandi123'),
    ]);
    $this->actingAs($pengguna);

    Volt::test('profil.index')
        ->set('kata_sandi_lama', 'lamasandi123')
        ->set('kata_sandi_baru', 'barusandi123')
        ->set('kata_sandi_baru_confirmation', 'barusandi123')
        ->call('ubahSandi')
        ->assertHasNoErrors();

    expect(Hash::check('barusandi123', $pengguna->refresh()->kata_sandi))->toBeTrue();
});

it('menolak ubah kata sandi bila kata sandi lama salah', function () {
    $pengguna = Pengguna::factory()->create([
        'peran' => 'staf', 'kata_sandi' => Hash::make('lamasandi123'),
    ]);
    $this->actingAs($pengguna);

    Volt::test('profil.index')
        ->set('kata_sandi_lama', 'sandisalah')
        ->set('kata_sandi_baru', 'barusandi123')
        ->set('kata_sandi_baru_confirmation', 'barusandi123')
        ->call('ubahSandi')
        ->assertHasErrors('kata_sandi_lama');

    expect(Hash::check('lamasandi123', $pengguna->refresh()->kata_sandi))->toBeTrue();
});

it('menolak ubah kata sandi bila konfirmasi tidak cocok', function () {
    $pengguna = Pengguna::factory()->create([
        'peran' => 'staf', 'kata_sandi' => Hash::make('lamasandi123'),
    ]);
    $this->actingAs($pengguna);

    Volt::test('profil.index')
        ->set('kata_sandi_lama', 'lamasandi123')
        ->set('kata_sandi_baru', 'barusandi123')
        ->set('kata_sandi_baru_confirmation', 'bedasandi999')
        ->call('ubahSandi')
        ->assertHasErrors('kata_sandi_baru');
});
