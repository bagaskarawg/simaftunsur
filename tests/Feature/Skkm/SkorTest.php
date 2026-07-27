<?php

use App\Models\KegiatanKemahasiswaan;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
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
    expect($p->poin())->toBe(20); // Tabel 5: nasional juara 1

    // Tanpa capaian → tidak berpoin.
    $q = Prestasi::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'internasional', 'capaian' => null,
    ]);
    expect($q->poin())->toBe(0);
});

it('menghitung poin kegiatan (F6) & pengabdian (F7) dari rubrik jenis x peran', function () {
    $keg = KegiatanKemahasiswaan::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'organisasi', 'peran' => 'ketua_umum',
    ]);
    expect($keg->poin())->toBe(20); // Tabel 6: organisasi ketua umum

    $peng = PengabdianHibah::factory()->create([
        'mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'hibah_didanai', 'peran' => 'ketua',
    ]);
    expect($peng->poin())->toBe(35); // Tabel 7: hibah didanai ketua
});

it('mengakumulasi skor SKKM per mahasiswa', function () {
    Prestasi::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'nasional', 'capaian' => 'juara_1']); // 20
    Prestasi::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'lokal', 'capaian' => 'finalis']);    // 1
    KegiatanKemahasiswaan::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'organisasi', 'peran' => 'ketua_umum']); // 20
    KegiatanKemahasiswaan::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'seminar', 'peran' => 'peserta']);       // 1
    PengabdianHibah::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'jenis' => 'pengabdian_masyarakat', 'peran' => 'luar_kampus']); // 3

    $mahasiswa = $this->mahasiswa->fresh(['prestasi', 'kegiatanKemahasiswaan', 'pengabdianHibah']);

    // Tiap grup berisi ≤3 item → plafon tidak memotong.
    expect($mahasiswa->skorPrestasi())->toBe(21)
        ->and($mahasiswa->skorKegiatan())->toBe(21)
        ->and($mahasiswa->skorPengabdian())->toBe(3);
});

it('menerapkan plafon: maksimal 3 item poin tertinggi per grup per tahun', function () {
    // 4 prestasi tingkat & tahun sama; hanya 3 poin tertinggi dihitung.
    foreach (['juara_1', 'juara_2', 'juara_3', 'finalis'] as $capaian) {
        Prestasi::factory()->create([
            'mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'nasional',
            'capaian' => $capaian, 'tanggal' => '2025-05-01',
        ]);
    }

    // nasional 2025: 20 + 17 + 14 (finalis 8 dibuang oleh plafon) = 51.
    expect($this->mahasiswa->fresh('prestasi')->skorPrestasi())->toBe(51);
});

it('plafon dihitung terpisah per tahun kalender', function () {
    Prestasi::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'nasional', 'capaian' => 'juara_1', 'tanggal' => '2024-05-01']); // 20
    // 4 item di 2025 → hanya 3 tertinggi (20+17+14=51).
    foreach (['juara_1', 'juara_2', 'juara_3', 'finalis'] as $capaian) {
        Prestasi::factory()->create(['mahasiswa_id' => $this->mahasiswa->id, 'tingkat' => 'nasional', 'capaian' => $capaian, 'tanggal' => '2025-05-01']);
    }

    // 2024 (20) + 2025 (51) = 71.
    expect($this->mahasiswa->fresh('prestasi')->skorPrestasi())->toBe(71);
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
    ])->and($fitur['skor_kegiatan'])->toBe(20);
});
