<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use App\Models\TracerStudy;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->tif = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->tsi = ProgramStudi::create(['kode' => 'TSI', 'nama' => 'Teknik Sipil', 'jenjang' => 'S1']);

    // 2 mahasiswa aktif di TIF, 1 cuti di TSI.
    $a = Mahasiswa::factory()->untukProdi($this->tif)->create(['status' => 'aktif']);
    $b = Mahasiswa::factory()->untukProdi($this->tif)->create(['status' => 'aktif']);
    $c = Mahasiswa::factory()->untukProdi($this->tsi)->create(['status' => 'cuti']);

    foreach ([$a, $b, $c] as $m) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $m->id, 'semester' => 1, 'semester_ganjil_genap' => 'ganjil', 'ipk' => 3.00,
        ]);
    }

    Prestasi::factory()->create(['mahasiswa_id' => $a->id]);
});

it('mengagregasi ringkasan & rekap dengan benar', function () {
    $laporan = app(LaporanService::class);

    expect($laporan->ringkasan())
        ->toMatchArray([
            'total_mahasiswa' => 3,
            'mahasiswa_aktif' => 2,
            'total_prestasi'  => 1,
        ]);

    $prodi = $laporan->rekapProdi()->keyBy('kode');
    expect($prodi['TIF']['jumlah'])->toBe(2)
        ->and($prodi['TIF']['aktif'])->toBe(2)
        ->and($prodi['TSI']['jumlah'])->toBe(1)
        ->and($prodi['TSI']['aktif'])->toBe(0);

    $status = $laporan->rekapStatus()->keyBy('status');
    expect($status['aktif']['jumlah'])->toBe(2)
        ->and($status['cuti']['jumlah'])->toBe(1);
});

it('hanya dapat dilihat oleh yang berizin laporan.lihat', function () {
    // staf tidak punya laporan.lihat.
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf']));
    Volt::test('laporan.index')->assertForbidden();

    // dekan punya laporan.lihat.
    $this->actingAs(Pengguna::factory()->create(['peran' => 'dekan']));
    Volt::test('laporan.index')->assertOk()
        ->assertSee('Rekap per Program Studi')
        ->assertSee('Status Pekerjaan Alumni (Tracer)')
        ->assertSee('Prestasi per Tingkat');
});

it('merekap tracer & prestasi per kategori (selalu 4 kategori)', function () {
    $mhs = Mahasiswa::factory()->untukProdi($this->tif)->create();
    TracerStudy::factory()->create(['mahasiswa_id' => $mhs->id, 'status_pekerjaan' => 'bekerja']);
    Prestasi::factory()->create(['mahasiswa_id' => $mhs->id, 'tingkat' => 'nasional']);

    $laporan = app(LaporanService::class);

    $tracer = $laporan->rekapTracer()->keyBy('status');
    expect($tracer)->toHaveCount(4)
        ->and($tracer['bekerja']['jumlah'])->toBeGreaterThanOrEqual(1)
        ->and($tracer['belum_bekerja']['jumlah'])->toBe(0);

    $tingkat = $laporan->rekapPrestasiTingkat()->keyBy('tingkat');
    expect($tingkat)->toHaveCount(4)
        ->and($tingkat['nasional']['jumlah'])->toBeGreaterThanOrEqual(1);
});

it('membatasi ekspor CSV hanya untuk yang berizin laporan.ekspor', function () {
    // wd3 punya laporan.ekspor.
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));
    $this->get(route('laporan.ekspor.prodi'))
        ->assertOk()
        ->assertDownload('laporan-rekap-prodi.csv');

    // dekan boleh lihat tapi TIDAK boleh ekspor.
    $this->actingAs(Pengguna::factory()->create(['peran' => 'dekan']));
    $this->get(route('laporan.ekspor.prodi'))->assertForbidden();
});
