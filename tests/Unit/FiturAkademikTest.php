<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// T1 whitebox: butuh DB (fitur diturunkan dari NilaiIpkSemester).
uses(TestCase::class, RefreshDatabase::class);

/** Muat data emas BAB III (deret IPK + fitur acuan). */
function dataEmas(): array
{
    return json_decode(file_get_contents(base_path('tests/fixtures/golden_bab3.json')), true);
}

/** Seed satu mahasiswa dari deret IPK per-semester. */
function mahasiswaDariDeret(ProgramStudi $prodi, array $ipkPerSemester): Mahasiswa
{
    $m = Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);
    foreach ($ipkPerSemester as $i => $ipk) {
        $s = $i + 1;
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $m->id, 'semester' => $s, 'ipk' => $ipk,
            'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }

    return $m->fresh('nilaiIpkSemester');
}

beforeEach(function () {
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
});

it('U-01: fitur akademik (IPK rata, terakhir, tren, konsistensi) M1–M9 sesuai data emas', function () {
    foreach (dataEmas()['mahasiswa'] as $emas) {
        $m = mahasiswaDariDeret($this->prodi, $emas['ipk_per_semester']);

        expect($m->ipkRataRata())->toEqualWithDelta($emas['ipk_rata_rata'], 1e-4, $emas['kode'])
            ->and($m->ipkTerakhir())->toEqualWithDelta($emas['ipk_terakhir'], 1e-4, $emas['kode'])
            ->and($m->tren())->toEqualWithDelta($emas['tren'], 1e-4, $emas['kode'])
            ->and($m->konsistensi())->toEqualWithDelta($emas['konsistensi'], 1e-4, $emas['kode']);
    }
});

it('U-01: konsistensi adalah standar deviasi populasi (kecil = stabil)', function () {
    // Deret konstan → std 0; deret berfluktuasi → std > 0.
    $stabil = mahasiswaDariDeret($this->prodi, [3.50, 3.50, 3.50, 3.50]);
    $fluktuatif = mahasiswaDariDeret($this->prodi, [3.20, 3.80, 3.10, 3.90]);

    expect($stabil->konsistensi())->toBe(0.0)
        ->and($fluktuatif->konsistensi())->toBeGreaterThan($stabil->konsistensi());
});
