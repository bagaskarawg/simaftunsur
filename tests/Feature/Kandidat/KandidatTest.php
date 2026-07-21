<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use App\Services\KandidatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

/**
 * Helper: mahasiswa aktif dengan IPK seragam pada sejumlah semester.
 */
function mahasiswaKandidat(ProgramStudi $prodi, float $ipk, int $semester = 4): Mahasiswa
{
    $mahasiswa = Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);

    for ($s = 1; $s <= $semester; $s++) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'semester' => $s,
            'ipk' => $ipk,
            'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }

    return $mahasiswa;
}

beforeEach(function () {
    $this->prodi = ProgramStudi::create([
        'kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1',
    ]);
});

it('menyaring kandidat sesuai ambang IPK program', function () {
    mahasiswaKandidat($this->prodi, 3.60);   // memenuhi (>= 3.00)
    mahasiswaKandidat($this->prodi, 2.80);   // di bawah ambang beasiswa_prestasi

    $hasil = app(KandidatService::class)->daftar('beasiswa_prestasi');

    expect($hasil['total'])->toBe(1)
        ->and($hasil['kandidat']->first()['ipk_rata_rata'])->toBe(3.60);
});

it('program mawapres hanya memuat mahasiswa yang punya prestasi berpoin', function () {
    $berprestasi = mahasiswaKandidat($this->prodi, 3.40);
    Prestasi::factory()->create([
        'mahasiswa_id' => $berprestasi->id,
        'tingkat' => 'nasional',
        'capaian' => 'juara_1',
    ]);

    mahasiswaKandidat($this->prodi, 3.70); // IPK tinggi TAPI tanpa prestasi → tersaring keluar

    $hasil = app(KandidatService::class)->daftar('mawapres');

    expect($hasil['total'])->toBe(1)
        ->and($hasil['kandidat']->first()['mahasiswa']->id)->toBe($berprestasi->id)
        ->and($hasil['kandidat']->first()['skor_prestasi'])->toBeGreaterThan(0);
});

it('mengurutkan kandidat menurun pada kolom terpilih', function () {
    $rendah = mahasiswaKandidat($this->prodi, 3.10);
    $tinggi = mahasiswaKandidat($this->prodi, 3.90);

    $hasil = app(KandidatService::class)->daftar('beasiswa_prestasi', [
        'urut' => 'ipk_rata_rata', 'arah' => 'desc',
    ]);

    expect($hasil['kandidat']->first()['mahasiswa']->id)->toBe($tinggi->id)
        ->and($hasil['kandidat']->last()['mahasiswa']->id)->toBe($rendah->id);
});

it('menolak kolom pengurutan di luar daftar putih dan jatuh ke bawaan preset', function () {
    mahasiswaKandidat($this->prodi, 3.50);

    $hasil = app(KandidatService::class)->daftar('beasiswa_prestasi', ['urut' => 'nama; DROP TABLE']);

    // Kolom invalid diabaikan → pakai bawaan preset (ipk_rata_rata).
    expect($hasil['kolom_urut'])->toBe('ipk_rata_rata');
});

it('halaman kandidat menolak akses tanpa izin', function () {
    // staf_prodi TIDAK punya izin kandidat.lihat.
    $pengguna = Pengguna::factory()->create(['peran' => 'staf_prodi']);

    $this->actingAs($pengguna);

    Volt::test('kandidat.index')->assertStatus(403);
});

it('halaman kandidat menampilkan kandidat bagi peran berizin', function () {
    $mhs = mahasiswaKandidat($this->prodi, 3.75);
    $pengguna = Pengguna::factory()->create(['peran' => 'wd3']);

    $this->actingAs($pengguna);

    Volt::test('kandidat.index')
        ->set('programKunci', 'beasiswa_prestasi')
        ->assertOk()
        ->assertSee($mhs->nama);
});
