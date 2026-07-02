<?php

use App\Models\KegiatanKemahasiswaan;
use App\Models\Mahasiswa;
use App\Models\PengabdianHibah;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use App\Services\KlasterisasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->mahasiswa = Mahasiswa::factory()->untukProdi($this->prodi)->create(['status' => 'aktif']);
});

it('menghitung poin prestasi (F5) dari rubrik tingkat x capaian', function () {
    $p = Prestasi::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'nasional', 'capaian' => 'juara_1',
    ]);
    expect($p->poin())->toBe(80);

    // Tanpa capaian → tidak berpoin.
    $q = Prestasi::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'internasional', 'capaian' => null,
    ]);
    expect($q->poin())->toBe(0);
});

it('menghitung poin kegiatan (F6) & pengabdian (F7) dari rubrik jenis x peran', function () {
    $keg = KegiatanKemahasiswaan::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'organisasi', 'peran' => 'ketua',
    ]);
    expect($keg->poin())->toBe(40);

    $peng = PengabdianHibah::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'hibah_didanai', 'peran' => 'ketua',
    ]);
    expect($peng->poin())->toBe(50);
});

it('mengakumulasi skor SKKM per mahasiswa', function () {
    Prestasi::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'nasional', 'capaian' => 'juara_1']); // 80
    Prestasi::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'lokal', 'capaian' => 'finalis']);    // 10
    KegiatanKemahasiswaan::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'organisasi', 'peran' => 'ketua']); // 40
    KegiatanKemahasiswaan::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'seminar', 'peran' => 'peserta']);  // 5
    PengabdianHibah::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'pengabdian_masyarakat', 'peran' => 'peserta_aktif']); // 15

    $mahasiswa = $this->mahasiswa->fresh(['prestasi', 'kegiatanKemahasiswaan', 'pengabdianHibah']);

    expect($mahasiswa->skorPrestasi())->toBe(90)
        ->and($mahasiswa->skorKegiatan())->toBe(45)
        ->and($mahasiswa->skorPengabdian())->toBe(15);
});

it('menyertakan 7 fitur (termasuk skor SKKM) pada vektor fitur klasterisasi', function () {
    for ($s = 1; $s <= 3; $s++) {
        \App\Models\NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $this->mahasiswa->id, 'semester' => $s, 'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }
    KegiatanKemahasiswaan::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'organisasi', 'peran' => 'ketua']);

    $layak = app(KlasterisasiService::class)->ambilMahasiswaLayak();
    $fitur = (new ReflectionMethod(KlasterisasiService::class, 'snapshotFitur'))
        ->invoke(app(KlasterisasiService::class), $layak->firstWhere('id', $this->mahasiswa->id));

    expect($fitur)->toHaveKeys([
        'ipk_rata_rata', 'ipk_terakhir', 'tren', 'konsistensi',
        'skor_prestasi', 'skor_kegiatan', 'skor_pengabdian',
    ])->and($fitur['skor_kegiatan'])->toBe(40);
});
