<?php

use App\Models\KlasterisasiEksekusi;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\ProgramStudi;
use App\Services\KlasterisasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Helper: buat mahasiswa aktif beserta sejumlah catatan IPK.
 */
function mahasiswaDenganIpk(ProgramStudi $prodi, int $jumlahIpk): Mahasiswa
{
    $mahasiswa = Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);

    for ($semester = 1; $semester <= $jumlahIpk; $semester++) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id'          => $mahasiswa->id,
            'semester'              => $semester,
            'semester_ganjil_genap' => $semester % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }

    return $mahasiswa;
}

/**
 * Tanggapan tiruan service Python untuk daftar id mahasiswa.
 *
 * @param  array<int, int>  $idMahasiswa
 */
function tanggapanKlasterTiruan(array $idMahasiswa): array
{
    return [
        'k_terpilih'         => 2,
        'metode_pemilihan_k' => 'otomatis (Silhouette tertinggi)',
        'fitur_dipakai'      => ['ipk_rata_rata', 'ipk_terakhir', 'tren', 'konsistensi', 'semester_aktif'],
        'skema_penskalaan'   => 'standard',
        'jumlah_data'        => count($idMahasiswa),
        'metrik'             => ['inertia' => 12.3, 'silhouette' => 0.55, 'davies_bouldin' => 0.7],
        'evaluasi_k'         => [
            ['k' => 2, 'inertia' => 12.3, 'silhouette' => 0.55, 'davies_bouldin' => 0.7],
            ['k' => 3, 'inertia' => 9.1, 'silhouette' => 0.41, 'davies_bouldin' => 0.9],
        ],
        'profil_klaster'     => [
            ['cluster' => 0, 'jumlah' => count($idMahasiswa), 'centroid' => ['ipk_rata_rata' => 3.5], 'label_deskriptif' => 'Berprestasi'],
        ],
        'hasil'              => array_map(
            fn ($id, $i) => ['id' => $id, 'cluster' => $i % 2, 'pca_x' => 0.1 * $i, 'pca_y' => -0.1 * $i],
            $idMahasiswa,
            array_keys($idMahasiswa),
        ),
        'peringatan'         => ['Volume data di bawah ambang minimum.'],
    ];
}

beforeEach(function () {
    $this->prodi = ProgramStudi::create([
        'kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1',
    ]);
});

it('hanya mengikutsertakan mahasiswa aktif dengan minimal 3 catatan IPK', function () {
    $layak = mahasiswaDenganIpk($this->prodi, 3);
    mahasiswaDenganIpk($this->prodi, 2); // kurang dari 3 → tidak layak
    $cuti = mahasiswaDenganIpk($this->prodi, 4);
    $cuti->update(['status' => 'cuti']); // bukan aktif → tidak layak

    $layakIds = app(KlasterisasiService::class)->ambilMahasiswaLayak()->pluck('id');

    expect($layakIds)->toContain($layak->id)
        ->and($layakIds)->not->toContain($cuti->id)
        ->and($layakIds)->toHaveCount(1);
});

it('mengirim fitur termetakan ke service dan menyimpan hasil + anggota', function () {
    $m1 = mahasiswaDenganIpk($this->prodi, 3);
    $m2 = mahasiswaDenganIpk($this->prodi, 4);
    $m3 = mahasiswaDenganIpk($this->prodi, 5);
    $ids = [$m1->id, $m2->id, $m3->id];

    Http::fake([
        '*/sehat'        => Http::response(['status' => 'ok']),
        '*/klasterisasi' => Http::response(tanggapanKlasterTiruan($ids)),
    ]);

    $eksekusi = app(KlasterisasiService::class)->jalankan(['k_min' => 2, 'k_max' => 4]);

    // Header tersimpan dengan metrik yang benar.
    expect($eksekusi)->toBeInstanceOf(KlasterisasiEksekusi::class)
        ->and($eksekusi->k_terpilih)->toBe(2)
        ->and($eksekusi->silhouette)->toBe(0.55)
        ->and($eksekusi->jumlah_data)->toBe(3)
        ->and($eksekusi->anggota()->count())->toBe(3)
        ->and($eksekusi->peringatan)->not->toBeEmpty();

    // Payload yang dikirim memuat fitur termetakan untuk tiap mahasiswa.
    Http::assertSent(function ($request) use ($ids) {
        if (! str_contains($request->url(), '/klasterisasi')) {
            return false;
        }
        $data = $request['data'];

        return count($data) === 3
            && collect($data)->pluck('id')->sort()->values()->all() === collect($ids)->sort()->values()->all()
            && array_key_exists('ipk_rata_rata', $data[0])
            && array_key_exists('tren', $data[0])
            && array_key_exists('program_studi', $data[0]);
    });
});

it('menolak menjalankan bila data layak kurang dari 3', function () {
    mahasiswaDenganIpk($this->prodi, 3);
    mahasiswaDenganIpk($this->prodi, 3);

    Http::fake(['*/sehat' => Http::response(['status' => 'ok'])]);

    app(KlasterisasiService::class)->jalankan();
})->throws(RuntimeException::class);

it('melempar galat ramah saat service menolak (HTTP 422)', function () {
    mahasiswaDenganIpk($this->prodi, 3);
    mahasiswaDenganIpk($this->prodi, 3);
    mahasiswaDenganIpk($this->prodi, 3);

    Http::fake([
        '*/klasterisasi' => Http::response(['detail' => 'Data terlalu sedikit.'], 422),
    ]);

    app(KlasterisasiService::class)->jalankan();
})->throws(RuntimeException::class, 'Service menolak permintaan: Data terlalu sedikit.');

it('menghitung kesiapan data terhadap ambang volume', function () {
    mahasiswaDenganIpk($this->prodi, 3);          // layak
    mahasiswaDenganIpk($this->prodi, 4);          // layak
    mahasiswaDenganIpk($this->prodi, 1);          // aktif tapi <3 catatan
    $cuti = mahasiswaDenganIpk($this->prodi, 5);  // cukup IPK tapi tidak aktif
    $cuti->update(['status' => 'cuti']);

    $kesiapan = app(KlasterisasiService::class)->kesiapan();

    expect($kesiapan['total'])->toBe(4)
        ->and($kesiapan['aktif'])->toBe(3)
        ->and($kesiapan['layak'])->toBe(2)
        ->and($kesiapan['aktif_kurang_ipk'])->toBe(1)
        ->and($kesiapan['ambang'])->toBe(100)
        ->and($kesiapan['kurang'])->toBe(98)
        ->and($kesiapan['siap'])->toBeFalse()
        ->and($kesiapan['cukup_untuk_jalan'])->toBeFalse();
});
