<?php

use App\Imports\PenggunaMassalImport;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

function csvPenggunaSementara(array $baris): string
{
    $path = tempnam(sys_get_temp_dir(), 'png_test_').'.csv';
    $handle = fopen($path, 'w');
    foreach ($baris as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

$header = ['nip', 'nama', 'email', 'peran', 'kata_sandi'];

it('mengimpor pengguna baru; kata sandi default bila kosong', function () use ($header) {
    $path = csvPenggunaSementara([
        $header,
        ['198001012005011001', 'Andi Wijaya', 'andi@ft.unsur.ac.id', 'kaprodi', ''],
        ['199002022010012002', 'Rina Marlina', 'rina@ft.unsur.ac.id', 'staf_wd3', 'rahasiabaru1'],
    ]);

    $import = new PenggunaMassalImport();
    Excel::import($import, $path);

    expect(Pengguna::count())->toBe(2)
        ->and($import->hasil->ditambah)->toBe(2)
        ->and($import->hasil->gagal)->toBeEmpty();

    $andi = Pengguna::where('nip', '198001012005011001')->first();
    expect($andi->peran)->toBe('kaprodi')
        ->and(Hash::check('rahasia123', $andi->kata_sandi))->toBeTrue(); // default

    $rina = Pengguna::where('nip', '199002022010012002')->first();
    expect(Hash::check('rahasiabaru1', $rina->kata_sandi))->toBeTrue();

    unlink($path);
});

it('melaporkan baris dengan peran tidak valid', function () use ($header) {
    $path = csvPenggunaSementara([
        $header,
        ['198001012005011009', 'Salah Peran', '', 'superuser', ''],
    ]);

    $import = new PenggunaMassalImport();
    Excel::import($import, $path);

    expect(Pengguna::count())->toBe(0)
        ->and($import->hasil->gagal)->toHaveCount(1);

    unlink($path);
});

it('memperbarui pengguna yang NIP-nya sudah ada tanpa mengubah sandi', function () use ($header) {
    $lama = Pengguna::factory()->create([
        'nip' => '198001012005011001', 'peran' => 'staf_wd3', 'kata_sandi' => Hash::make('sandilama1'),
    ]);

    $path = csvPenggunaSementara([
        $header,
        ['198001012005011001', 'Andi Updated', 'andi@ft.unsur.ac.id', 'kaprodi', ''],
    ]);

    $import = new PenggunaMassalImport();
    Excel::import($import, $path);

    expect(Pengguna::count())->toBe(1)
        ->and($import->hasil->ditimpa)->toBe(1);

    $lama->refresh();
    expect($lama->peran)->toBe('kaprodi')
        ->and($lama->nama)->toBe('Andi Updated')
        ->and(Hash::check('sandilama1', $lama->kata_sandi))->toBeTrue(); // sandi tak berubah

    unlink($path);
});
