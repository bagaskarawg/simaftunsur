<?php

use App\Models\KegiatanKemahasiswaan;
use App\Models\Mahasiswa;
use App\Models\PengabdianHibah;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->mhs = Mahasiswa::factory()->untukProdi($this->prodi)->create(['status' => 'aktif']);
});

it('U-04: poin prestasi (F5) sesuai rubrik Tabel 5 (tingkat × capaian)', function () {
    $kasus = [
        ['internasional', 'juara_1', 25], ['internasional', 'finalis', 10],
        ['nasional', 'juara_1', 20], ['nasional', 'juara_3', 14],
        ['regional', 'juara_2', 8], ['lokal', 'juara_1', 8], ['lokal', 'finalis', 1],
    ];
    foreach ($kasus as [$tingkat, $capaian, $poin]) {
        $p = Prestasi::factory()->make(['tingkat' => $tingkat, 'capaian' => $capaian]);
        expect($p->poin())->toBe($poin, "$tingkat/$capaian");
    }
});

it('U-04: poin kegiatan (F6) sesuai rubrik Tabel 6 (jenis × peran)', function () {
    $kasus = [
        ['organisasi', 'ketua_umum', 20], ['organisasi', 'anggota_pengurus', 10],
        ['ukm', 'ketua_ukm', 10],
        ['kepanitiaan', 'ketua', 10], ['kepanitiaan', 'anggota', 5],
        ['seminar', 'pembicara', 8], ['seminar', 'peserta', 1],
    ];
    foreach ($kasus as [$jenis, $peran, $poin]) {
        $k = KegiatanKemahasiswaan::factory()->make(['jenis' => $jenis, 'peran' => $peran]);
        expect($k->poin())->toBe($poin, "$jenis/$peran");
    }
});

it('U-04: poin pengabdian (F7) sesuai rubrik Tabel 7 (jenis × peran)', function () {
    $kasus = [
        ['pimnas', 'ketua', 40], ['pimnas', 'anggota', 35],
        ['hibah_didanai', 'ketua', 35], ['proposal_lolos', 'anggota', 10],
        ['pengabdian_masyarakat', 'dalam_kampus', 1], ['pengabdian_masyarakat', 'luar_kampus', 3],
    ];
    foreach ($kasus as [$jenis, $peran, $poin]) {
        $p = PengabdianHibah::factory()->make(['jenis' => $jenis, 'peran' => $peran]);
        expect($p->poin())->toBe($poin, "$jenis/$peran");
    }
});

it('U-04: plafon akumulasi — maksimal 3 item poin tertinggi per grup per tahun', function () {
    // 4 prestasi nasional 2025 → hanya 3 tertinggi (20+17+14), finalis 8 dibuang.
    foreach (['juara_1', 'juara_2', 'juara_3', 'finalis'] as $c) {
        Prestasi::factory()->create([
            'mahasiswa_id' => $this->mhs->id, 'tingkat' => 'nasional', 'capaian' => $c, 'tanggal' => '2025-05-01',
        ]);
    }
    expect($this->mhs->fresh('prestasi')->skorPrestasi())->toBe(51);
});

it('U-04: plafon dihitung terpisah per tahun kalender & per grup', function () {
    // nasional 2024 (juara_1 = 20) + 4 nasional 2025 (top-3 = 51) = 71.
    Prestasi::factory()->create(['mahasiswa_id' => $this->mhs->id, 'tingkat' => 'nasional', 'capaian' => 'juara_1', 'tanggal' => '2024-05-01']);
    foreach (['juara_1', 'juara_2', 'juara_3', 'finalis'] as $c) {
        Prestasi::factory()->create(['mahasiswa_id' => $this->mhs->id, 'tingkat' => 'nasional', 'capaian' => $c, 'tanggal' => '2025-05-01']);
    }
    // Tingkat berbeda (lokal 2025) dihitung sebagai grup terpisah, tak kena plafon nasional.
    Prestasi::factory()->create(['mahasiswa_id' => $this->mhs->id, 'tingkat' => 'lokal', 'capaian' => 'juara_1', 'tanggal' => '2025-06-01']); // 8

    expect($this->mhs->fresh('prestasi')->skorPrestasi())->toBe(71 + 8);
});

it('U-04: capaian kosong tidak berpoin', function () {
    $p = Prestasi::factory()->make(['tingkat' => 'internasional', 'capaian' => null]);
    expect($p->poin())->toBe(0);
});
