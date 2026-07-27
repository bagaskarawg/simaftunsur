<?php

use App\Imports\IpkMassalImport;
use App\Imports\IpkSatuMahasiswaImport;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

/**
 * Helper: tulis CSV ringan ke file temporer dan kembalikan path-nya.
 *
 * @param  array<int, array<int, mixed>>  $baris
 */
function csvSementara(array $baris): string
{
    $path = tempnam(sys_get_temp_dir(), 'ipk_test_').'.csv';
    $handle = fopen($path, 'w');
    foreach ($baris as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

beforeEach(function () {
    $this->prodi = ProgramStudi::create([
        'kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1',
    ]);
});

/* =========================================================================
 *  IpkSatuMahasiswaImport
 * ========================================================================= */

it('mengimpor IPK satu mahasiswa dari CSV valid', function () {
    $mahasiswa = Mahasiswa::factory()
        ->untukProdi($this->prodi)
        ->denganSemester(3)
        ->create();

    $path = csvSementara([
        ['semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus'],
        [1, '2025/2026', 'ganjil', 3.45, 22, 22],
        [2, '2025/2026', 'genap',  3.52, 24, 24],
    ]);

    $import = new IpkSatuMahasiswaImport($mahasiswa);
    Excel::import($import, $path);

    expect($import->hasil->ditambah)->toBe(2);
    expect($import->hasil->ditimpa)->toBe(0);
    expect($import->hasil->gagal)->toBeEmpty();
    expect(NilaiIpkSemester::where('mahasiswa_id', $mahasiswa->id)->count())->toBe(2);
});

it('menimpa IPK yang sudah ada (upsert by mahasiswa+semester)', function () {
    $mahasiswa = Mahasiswa::factory()->untukProdi($this->prodi)->create();

    NilaiIpkSemester::create([
        'mahasiswa_id'          => $mahasiswa->id,
        'semester'              => 1,
        'tahun_akademik'        => '2024/2025',
        'semester_ganjil_genap' => 'ganjil',
        'ipk'                   => 2.50,
        'sks_diambil'           => 20,
        'sks_lulus'             => 18,
    ]);

    $path = csvSementara([
        ['semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus'],
        [1, '2025/2026', 'ganjil', 3.80, 24, 24], // ← semester 1 sama, isi baru
    ]);

    $import = new IpkSatuMahasiswaImport($mahasiswa);
    Excel::import($import, $path);

    expect($import->hasil->ditimpa)->toBe(1);
    expect($import->hasil->ditambah)->toBe(0);

    $catatan = NilaiIpkSemester::where('mahasiswa_id', $mahasiswa->id)->first();
    expect((float) $catatan->ipk)->toBe(3.80);
    expect($catatan->tahun_akademik)->toBe('2025/2026');
});

it('melaporkan baris invalid tanpa menghentikan baris valid', function () {
    $mahasiswa = Mahasiswa::factory()->untukProdi($this->prodi)->create();

    $path = csvSementara([
        ['semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus'],
        [1, '2025/2026', 'ganjil', 3.45, 22, 22],          // valid
        [99, '2025/2026', 'ganjil', 3.50, 22, 22],         // semester di luar 1-14
        [3, '2025-2026', 'ganjil', 3.40, 22, 22],          // format tahun salah
        [4, '2025/2026', 'genap', 5.00, 22, 22],           // ipk > 4
    ]);

    $import = new IpkSatuMahasiswaImport($mahasiswa);
    Excel::import($import, $path);

    expect($import->hasil->ditambah)->toBe(1);
    expect($import->hasil->gagal)->toHaveCount(3);
    expect($import->hasil->gagal[0]['baris'])->toBe(3); // header baris 1, data mulai baris 2; baris invalid pertama = baris 3
});

/* =========================================================================
 *  IpkMassalImport
 * ========================================================================= */

it('mengimpor IPK massal dengan kolom NPM', function () {
    $m1 = Mahasiswa::factory()->untukProdi($this->prodi)->create(['npm' => '20200000001']);
    $m2 = Mahasiswa::factory()->untukProdi($this->prodi)->create(['npm' => '20200000002']);

    $path = csvSementara([
        ['npm', 'semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus'],
        ['20200000001', 1, '2025/2026', 'ganjil', 3.45, 22, 22],
        ['20200000001', 2, '2025/2026', 'genap',  3.60, 24, 24],
        ['20200000002', 1, '2025/2026', 'ganjil', 3.20, 22, 20],
    ]);

    $import = new IpkMassalImport();
    Excel::import($import, $path);

    expect($import->hasil->ditambah)->toBe(3);
    expect($import->hasil->gagal)->toBeEmpty();
    expect(NilaiIpkSemester::where('mahasiswa_id', $m1->id)->count())->toBe(2);
    expect(NilaiIpkSemester::where('mahasiswa_id', $m2->id)->count())->toBe(1);
});

it('melaporkan NPM yang tidak terdaftar', function () {
    Mahasiswa::factory()->untukProdi($this->prodi)->create(['npm' => '20200000001']);

    $path = csvSementara([
        ['npm', 'semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus'],
        ['20200000001', 1, '2025/2026', 'ganjil', 3.45, 22, 22], // valid
        ['99999999999', 1, '2025/2026', 'ganjil', 3.45, 22, 22], // NPM tidak terdaftar
    ]);

    $import = new IpkMassalImport();
    Excel::import($import, $path);

    expect($import->hasil->ditambah)->toBe(1);
    expect($import->hasil->gagal)->toHaveCount(1);
    expect($import->hasil->gagal[0]['pesan'])->toContain('99999999999');
});

it('upsert massal: NPM+semester sama menimpa data lama', function () {
    $m = Mahasiswa::factory()->untukProdi($this->prodi)->create(['npm' => '20200000001']);

    NilaiIpkSemester::create([
        'mahasiswa_id'          => $m->id,
        'semester'              => 1,
        'tahun_akademik'        => '2024/2025',
        'semester_ganjil_genap' => 'ganjil',
        'ipk'                   => 2.00,
        'sks_diambil'           => 20,
        'sks_lulus'             => 18,
    ]);

    $path = csvSementara([
        ['npm', 'semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus'],
        ['20200000001', 1, '2025/2026', 'ganjil', 3.99, 24, 24],
    ]);

    $import = new IpkMassalImport();
    Excel::import($import, $path);

    expect($import->hasil->ditimpa)->toBe(1);
    expect((float) $m->fresh()->nilaiIpkSemester->first()->ipk)->toBe(3.99);
});
