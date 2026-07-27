<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\ProgramStudi;
use App\Services\KlasterisasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** Mahasiswa aktif dengan sejumlah catatan IPK. */
function mhsCatatan(ProgramStudi $prodi, int $jumlahIpk): Mahasiswa
{
    $m = Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);
    for ($s = 1; $s <= $jumlahIpk; $s++) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $m->id, 'semester' => $s,
            'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }

    return $m;
}

beforeEach(function () {
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
});

it('U-05: mahasiswa dengan < 3 catatan IPK DIKECUALIKAN dari kohort (batas 2/3)', function () {
    $layak = mhsCatatan($this->prodi, 3);   // tepat 3 → layak
    $tidak = mhsCatatan($this->prodi, 2);   // 2 → tidak layak

    $ids = app(KlasterisasiService::class)->ambilMahasiswaLayak()->pluck('id');

    expect($ids)->toContain($layak->id)
        ->and($ids)->not->toContain($tidak->id);
});

it('U-05: klasterisasi DITOLAK bila mahasiswa layak < 3 (hard block)', function () {
    mhsCatatan($this->prodi, 3);
    mhsCatatan($this->prodi, 3); // hanya 2 layak

    app(KlasterisasiService::class)->jalankan();
})->throws(RuntimeException::class);

it('U-05: volume < 100 TIDAK memblokir — hanya ditandai belum ideal (peringatan)', function () {
    // 3 mahasiswa layak: di bawah ambang ideal (100) tapi cukup untuk dijalankan.
    mhsCatatan($this->prodi, 3);
    mhsCatatan($this->prodi, 3);
    mhsCatatan($this->prodi, 4);

    $kesiapan = app(KlasterisasiService::class)->kesiapan();

    expect($kesiapan['layak'])->toBe(3)
        ->and($kesiapan['ambang'])->toBe(100)
        ->and($kesiapan['siap'])->toBeFalse()          // < 100 → belum ideal
        ->and($kesiapan['cukup_untuk_jalan'])->toBeTrue() // tetap boleh dijalankan
        ->and($kesiapan['persen'])->toBeLessThan(100);
    // Penolakan keras HANYA saat < 3 (lihat uji di atas). Data < 100 ditandai
    // indikatif lewat peringatan pipeline, bukan diblokir.
});
