<?php

use App\Models\KegiatanKemahasiswaan;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\ProgramStudi;
use App\Services\KlasterisasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Catatan: rubrik & plafon SKKM diuji tuntas di tests/Unit/RubrikSkkmTest.php
// (U-04). Berkas ini hanya menguji integrasi skor SKKM ke vektor fitur klaster.

beforeEach(function () {
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->mahasiswa = Mahasiswa::factory()->untukProdi($this->prodi)->create(['status' => 'aktif']);
});

it('menyertakan 7 fitur (termasuk skor SKKM) pada vektor fitur klasterisasi', function () {
    for ($s = 1; $s <= 3; $s++) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $this->mahasiswa->id, 'semester' => $s, 'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }
    KegiatanKemahasiswaan::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'organisasi', 'peran' => 'ketua_umum']);

    $layak = app(KlasterisasiService::class)->ambilMahasiswaLayak();
    $fitur = (new ReflectionMethod(KlasterisasiService::class, 'snapshotFitur'))
        ->invoke(app(KlasterisasiService::class), $layak->firstWhere('id', $this->mahasiswa->id));

    expect($fitur)->toHaveKeys([
        'ipk_rata_rata', 'ipk_terakhir', 'tren', 'konsistensi',
        'skor_prestasi', 'skor_kegiatan', 'skor_pengabdian',
    ])->and($fitur['skor_kegiatan'])->toBe(20); // organisasi ketua umum (Tabel 6)
});
