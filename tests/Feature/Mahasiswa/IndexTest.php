<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf']));
});

/** Helper: mahasiswa dengan satu catatan IPK bernilai $ipk. */
function mhsDenganIpk(ProgramStudi $prodi, string $jk, float $ipk): Mahasiswa
{
    $m = Mahasiswa::factory()->untukProdi($prodi)->create(['jenis_kelamin' => $jk]);
    NilaiIpkSemester::factory()->create([
        'mahasiswa_id' => $m->id, 'semester' => 1, 'semester_ganjil_genap' => 'ganjil',
        'ipk' => $ipk, 'sks_diambil' => 20, 'sks_lulus' => 20,
    ]);

    return $m;
}

it('memfilter mahasiswa berdasarkan jenis kelamin', function () {
    $laki = mhsDenganIpk($this->prodi, 'L', 3.0);
    $perempuan = mhsDenganIpk($this->prodi, 'P', 3.0);

    Volt::test('mahasiswa.index')
        ->set('filterJk', 'L')
        ->assertSee($laki->npm)
        ->assertDontSee($perempuan->npm);
});

it('memfilter mahasiswa berdasarkan rentang IPK rata-rata', function () {
    $tinggi = mhsDenganIpk($this->prodi, 'L', 3.80);
    $rendah = mhsDenganIpk($this->prodi, 'L', 2.40);

    Volt::test('mahasiswa.index')
        ->set('ipkMin', '3.50')
        ->assertSee($tinggi->npm)
        ->assertDontSee($rendah->npm);
});

it('menghapus mahasiswa terpilih secara massal', function () {
    $a = mhsDenganIpk($this->prodi, 'L', 3.0);
    $b = mhsDenganIpk($this->prodi, 'P', 3.0);

    Volt::test('mahasiswa.index')
        ->set('terpilih', [(string) $a->id, (string) $b->id])
        ->call('hapusTerpilih');

    expect(Mahasiswa::count())->toBe(0);
});
