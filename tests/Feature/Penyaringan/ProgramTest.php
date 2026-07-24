<?php

use App\Models\Pengguna;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('menolak akses CRUD program bagi peran tanpa izin (staf_prodi)', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_prodi']));

    Volt::test('program.index')->assertStatus(403);
});

it('mengizinkan wd3 membuka daftar program', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    Volt::test('program.index')->assertOk();
});

it('wd3 dapat membuat program beserta syarat', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    Volt::test('program.index')
        ->call('bukaTambah')
        ->set('nama', 'Beasiswa Unggulan 2026')
        ->set('jenis', 'beasiswa')
        ->set('syarat', [
            ['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '3.25', 'min_jumlah' => 1, 'wajib' => true],
            ['bidang' => 'skor_kegiatan', 'operator' => 'gte', 'nilai' => '40', 'min_jumlah' => 1, 'wajib' => false],
        ])
        ->call('simpan')
        ->assertHasNoErrors();

    $program = Program::with('syarat')->first();
    expect($program)->not->toBeNull()
        ->and($program->nama)->toBe('Beasiswa Unggulan 2026')
        ->and($program->syarat)->toHaveCount(2)
        ->and($program->syaratWajib()->count())->toBe(1)
        ->and($program->syarat->first()->label)->toContain('IPK rata-rata');
});

it('menolak kombinasi field-operator yang tidak valid', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    // status hanya menerima eq/in; gte tidak valid.
    Volt::test('program.index')
        ->call('bukaTambah')
        ->set('nama', 'Program Salah')
        ->set('syarat', [
            ['bidang' => 'status', 'operator' => 'gte', 'nilai' => 'aktif', 'min_jumlah' => 1, 'wajib' => true],
        ])
        ->call('simpan')
        ->assertHasErrors('syarat.0.operator');

    expect(Program::count())->toBe(0);
});

it('membangun label & menyimpan nilai JSON untuk operator in', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    Volt::test('program.index')
        ->call('bukaTambah')
        ->set('nama', 'Program In')
        ->set('syarat', [
            ['bidang' => 'status', 'operator' => 'in', 'nilai' => 'aktif, cuti', 'min_jumlah' => 1, 'wajib' => true],
        ])
        ->call('simpan')
        ->assertHasNoErrors();

    $syarat = Program::with('syarat')->first()->syarat->first();
    expect($syarat->nilaiTerdecode())->toBe(['aktif', 'cuti'])
        ->and($syarat->label)->toContain('salah satu dari');
});
