<?php

use App\Models\Mahasiswa;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('S-01: akses tanpa login dialihkan ke halaman login', function () {
    $this->get(route('mahasiswa.index'))->assertRedirect(route('login'));
    $this->get(route('klasterisasi.index'))->assertRedirect(route('login'));
});

it('S-02: akses lintas peran ditolak (403)', function () {
    // Staf Prodi → manajemen pengguna (khusus Administrator).
    $this->actingAs(Pengguna::factory()->stafProdi()->create())
        ->get(route('pengguna.index'))->assertForbidden();

    // Kaprodi (read-only) → aksi tulis data mahasiswa.
    $this->actingAs(Pengguna::factory()->kaprodi()->create());
    Volt::test('mahasiswa.baru')->assertForbidden();
});

it('S-03: payload XSS tidak tereksekusi (di-escape saat render)', function () {
    $prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    Mahasiswa::factory()->untukProdi($prodi)->create(['nama' => '<script>alert(1)</script>', 'status' => 'aktif']);
    $this->actingAs(Pengguna::factory()->admin()->create());

    // Blade meng-escape output → payload mentah tidak muncul di HTML.
    Volt::test('mahasiswa.index')
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('S-03: payload SQLi pada pencarian tidak merusak kueri', function () {
    $prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);
    $this->actingAs(Pengguna::factory()->admin()->create());

    // Eloquent memarametrikan kueri → payload diperlakukan sebagai teks biasa.
    Volt::test('mahasiswa.index')
        ->set('kataKunci', "' OR '1'='1")
        ->assertOk();
});

it('S-04: kata sandi disimpan sebagai hash, bukan teks polos', function () {
    $pengguna = Pengguna::factory()->create();

    expect($pengguna->kata_sandi)->not->toBe('rahasia123')
        ->and(str_starts_with($pengguna->kata_sandi, '$2y$'))->toBeTrue()
        ->and(Hash::check('rahasia123', $pengguna->kata_sandi))->toBeTrue();
});
