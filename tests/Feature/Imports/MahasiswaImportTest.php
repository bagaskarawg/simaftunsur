<?php

use App\Imports\MahasiswaMassalImport;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

/**
 * Helper: tulis CSV ke file temporer & kembalikan path-nya.
 *
 * @param  array<int, array<int, mixed>>  $baris
 */
function csvMahasiswaSementara(array $baris): string
{
    $path = tempnam(sys_get_temp_dir(), 'mhs_test_').'.csv';
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

$header = ['npm', 'nama', 'prodi', 'angkatan', 'semester_aktif', 'jenis_kelamin', 'status', 'email', 'nomor_telepon'];

it('mengimpor mahasiswa baru, memetakan prodi, dan menormalkan jenis kelamin/status', function () use ($header) {
    $path = csvMahasiswaSementara([
        $header,
        ['20200000001', 'Budi Santoso', 'TIF', 2020, 5, 'L', 'aktif', 'budi@x.ac.id', '0812'],
        ['20200000002', 'Siti Aminah', 'tif', 2021, 3, 'perempuan', '', '', ''],
    ]);

    $import = new MahasiswaMassalImport();
    Excel::import($import, $path);

    expect(Mahasiswa::count())->toBe(2)
        ->and($import->hasil->ditambah)->toBe(2)
        ->and($import->hasil->gagal)->toBeEmpty();

    $budi = Mahasiswa::where('npm', '20200000001')->first();
    expect($budi->program_studi_id)->toBe($this->prodi->id)
        ->and($budi->jenis_kelamin)->toBe('L');

    $siti = Mahasiswa::where('npm', '20200000002')->first();
    expect($siti->jenis_kelamin)->toBe('P')   // "perempuan" → P
        ->and($siti->status)->toBe('aktif');  // kosong → default aktif

    unlink($path);
});

it('melaporkan baris dengan kode prodi tidak terdaftar atau NPM invalid', function () use ($header) {
    $path = csvMahasiswaSementara([
        $header,
        ['20200000003', 'Andi', 'ZZZ', 2020, 5, 'L', 'aktif', '', ''], // prodi tak ada
        ['123', 'Pendek', 'TIF', 2020, 5, 'L', 'aktif', '', ''],       // npm bukan 11 char
    ]);

    $import = new MahasiswaMassalImport();
    Excel::import($import, $path);

    expect(Mahasiswa::count())->toBe(0)
        ->and($import->hasil->gagal)->toHaveCount(2);

    unlink($path);
});

it('memperbarui mahasiswa yang NPM-nya sudah ada (upsert)', function () use ($header) {
    Mahasiswa::factory()->untukProdi($this->prodi)->create([
        'npm' => '20200000001', 'nama' => 'Nama Lama',
    ]);

    $path = csvMahasiswaSementara([
        $header,
        ['20200000001', 'Nama Baru', 'TIF', 2020, 6, 'L', 'aktif', '', ''],
    ]);

    $import = new MahasiswaMassalImport();
    Excel::import($import, $path);

    expect(Mahasiswa::count())->toBe(1)
        ->and($import->hasil->ditimpa)->toBe(1)
        ->and($import->hasil->ditambah)->toBe(0)
        ->and(Mahasiswa::where('npm', '20200000001')->value('nama'))->toBe('Nama Baru');

    unlink($path);
});
