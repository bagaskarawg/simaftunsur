<?php

use App\Models\LogAktivitas;
use App\Models\Mahasiswa;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

it('mencatat log saat entitas bisnis dibuat', function () {
    $admin = Pengguna::factory()->admin()->create();
    $this->actingAs($admin);

    $prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $m = Mahasiswa::factory()->untukProdi($prodi)->create(['nama' => 'Uji Log']);

    $log = LogAktivitas::where('model', 'Mahasiswa')->where('aksi', 'dibuat')->latest()->first();

    expect($log)->not->toBeNull()
        ->and($log->pengguna_id)->toBe($admin->id)
        ->and($log->deskripsi)->toBe('Uji Log');
});

it('mencatat aktivitas masuk', function () {
    $pengguna = Pengguna::factory()->create(['peran' => 'staf']);

    event(new Login('web', $pengguna, false));

    expect(
        LogAktivitas::where('aksi', 'masuk')->where('pengguna_id', $pengguna->id)->exists()
    )->toBeTrue();
});

it('hanya admin yang dapat membuka log aktivitas', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']))
        ->get(route('log-aktivitas.index'))->assertForbidden();

    $this->actingAs(Pengguna::factory()->admin()->create())
        ->get(route('log-aktivitas.index'))->assertOk();
});
