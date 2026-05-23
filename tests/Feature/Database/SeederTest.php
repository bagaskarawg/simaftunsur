<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();
});

it('mengisi 4 prodi, 30 mahasiswa, dan minimal 120 catatan IPK', function () {
    expect(ProgramStudi::count())->toBe(4);
    expect(Mahasiswa::count())->toBe(30);
    expect(NilaiIpkSemester::count())->toBeGreaterThanOrEqual(120);
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
    $mahasiswa = Mahasiswa::with('nilaiIpkSemester')->first();

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
