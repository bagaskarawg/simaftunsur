<?php

use App\Models\Pengaturan;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->admin = Pengguna::factory()->create(['peran' => 'admin']);
});

it('hanya admin yang dapat mengakses pengaturan', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']))
        ->get(route('pengaturan.index'))->assertForbidden();

    $this->actingAs($this->admin)->get(route('pengaturan.index'))->assertOk();
});

it('menyimpan periode akademik & identitas fakultas', function () {
    $this->actingAs($this->admin);

    Volt::test('pengaturan.index')
        ->set('nama_fakultas', 'Fakultas Teknik UNSUR')
        ->set('tahun_akademik', '2025/2026')
        ->set('semester_aktif', 'genap')
        ->call('simpan')
        ->assertHasNoErrors();

    expect(Pengaturan::ambil('nama_fakultas'))->toBe('Fakultas Teknik UNSUR')
        ->and(Pengaturan::ambil('periode_tahun_akademik'))->toBe('2025/2026')
        ->and(Pengaturan::ambil('periode_semester'))->toBe('genap');
});

it('memvalidasi format tahun akademik', function () {
    $this->actingAs($this->admin);

    Volt::test('pengaturan.index')
        ->set('nama_fakultas', 'FT UNSUR')
        ->set('tahun_akademik', '2025-2026')
        ->call('simpan')
        ->assertHasErrors(['tahun_akademik']);
});

it('admin dapat mengunduh backup data JSON', function () {
    $respons = $this->actingAs($this->admin)->get(route('pengaturan.backup'));

    $respons->assertOk();
    expect($respons->headers->get('content-type'))->toContain('application/json');
});

it('non-admin tidak dapat mengunduh backup', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']))
        ->get(route('pengaturan.backup'))->assertForbidden();
});

it('helper ambil mengembalikan default bila kunci belum ada', function () {
    expect(Pengaturan::ambil('belum_ada', 'bawaan'))->toBe('bawaan');

    Pengaturan::simpan('belum_ada', 'terisi');
    expect(Pengaturan::ambil('belum_ada', 'bawaan'))->toBe('terisi');
});
