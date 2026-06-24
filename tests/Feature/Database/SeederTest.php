<?php

use App\Models\KegiatanPromosi;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use App\Models\TracerStudy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('mengisi 4 prodi & 30 mahasiswa aktif dengan 3–6 catatan IPK per mahasiswa', function () {
    expect(ProgramStudi::count())->toBe(4);

    // Kohort klasterisasi: 30 mahasiswa aktif, masing-masing punya catatan IPK.
    $aktif = Mahasiswa::where('status', 'aktif')->withCount('nilaiIpkSemester')->get();
    expect($aktif)->toHaveCount(30);

    // Seeder memberi tiap mahasiswa aktif min(semester_aktif, 4..6) catatan,
    // dengan semester_aktif 3..7 → 3–6 catatan IPK.
    $aktif->each(function ($mahasiswa) {
        expect($mahasiswa->nilai_ipk_semester_count)
            ->toBeGreaterThanOrEqual(3)
            ->toBeLessThanOrEqual(6);
    });

    // Total catatan berada pada rentang 30×3 .. 30×6.
    expect(NilaiIpkSemester::count())
        ->toBeGreaterThanOrEqual(90)
        ->toBeLessThanOrEqual(180);
});

it('mengisi data contoh modul pendukung (alumni, prestasi, tracer, promosi)', function () {
    // 8 alumni (status lulus) untuk tracer, terpisah dari kohort aktif.
    expect(Mahasiswa::where('status', 'lulus')->count())->toBe(8)
        ->and(TracerStudy::count())->toBe(8)
        ->and(Prestasi::count())->toBeGreaterThan(0)
        ->and(KegiatanPromosi::count())->toBe(10);
});

it('memastikan tiap prodi terisi minimal 5 mahasiswa', function () {
    $rekap = ProgramStudi::withCount('mahasiswa')->get();

    expect($rekap)->toHaveCount(4);

    foreach ($rekap as $prodi) {
        expect($prodi->mahasiswa_count)
            ->toBeGreaterThanOrEqual(5, "Prodi {$prodi->kode} hanya punya {$prodi->mahasiswa_count} mahasiswa");
    }
});

it('menghitung rata-rata IPK konsisten dengan data tersimpan', function () {
    $mahasiswa = Mahasiswa::has('nilaiIpkSemester')->with('nilaiIpkSemester')->first();

    $rataManual = round(
        $mahasiswa->nilaiIpkSemester->avg(fn ($n) => (float) $n->ipk),
        4,
    );

    expect($mahasiswa->ipkRataRata())->toEqualWithDelta($rataManual, 0.0001);
});

it('mengurutkan relasi nilai IPK berdasarkan semester naik', function () {
    $mahasiswa = Mahasiswa::factory()
        ->untukProdi(ProgramStudi::first())
        ->denganSemester(6)
        ->create();

    // Sengaja dibuat dengan urutan acak agar pengurutan relasi teruji.
    foreach ([3, 1, 5, 2, 4] as $semester) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id'          => $mahasiswa->id,
            'semester'              => $semester,
            'semester_ganjil_genap' => $semester % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }

    $urutan = $mahasiswa->load('nilaiIpkSemester')
        ->nilaiIpkSemester
        ->pluck('semester')
        ->all();

    expect($urutan)->toBe([1, 2, 3, 4, 5]);
});
