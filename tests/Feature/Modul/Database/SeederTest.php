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

it('mengisi 4 prodi & roster mahasiswa aktif dengan >= 3 catatan IPK per mahasiswa layak', function () {
    expect(ProgramStudi::count())->toBe(4);

    // Roster nyata dari berkas PMB: jumlah mahasiswa aktif melebihi ambang
    // minimum klasterisasi (100).
    expect(Mahasiswa::where('status', 'aktif')->count())->toBeGreaterThan(100);

    // Tiap mahasiswa aktif yang memiliki IPK punya >= 3 catatan (syarat fitur
    // tren & konsistensi bermakna).
    Mahasiswa::where('status', 'aktif')->has('nilaiIpkSemester')
        ->withCount('nilaiIpkSemester')->get()
        ->each(fn ($mahasiswa) => expect($mahasiswa->nilai_ipk_semester_count)->toBeGreaterThanOrEqual(3));

    expect(NilaiIpkSemester::count())->toBeGreaterThan(1000);
});

it('mengisi data contoh modul pendukung (tracer, prestasi, promosi)', function () {
    // Tracer memakai mahasiswa lulus NYATA (bukan alumni dummy); jumlahnya
    // mengikuti roster, dibatasi maksimal 8.
    $jumlahLulus = Mahasiswa::where('status', 'lulus')->count();

    expect(TracerStudy::count())->toBe(min(8, $jumlahLulus))
        ->and(Prestasi::count())->toBeGreaterThan(0)
        ->and(KegiatanPromosi::count())->toBe(10);
});

it('mengisi roster ketiga prodi Teknik dari berkas PMB', function () {
    // Berkas PMB memuat Teknik Informatika, Sipil, dan Industri; Teknik Mesin
    // tidak ada dalam berkas sehingga wajar tanpa mahasiswa.
    $perKode = ProgramStudi::withCount('mahasiswa')->get()->pluck('mahasiswa_count', 'kode');

    expect($perKode['TIF'])->toBeGreaterThan(0)
        ->and($perKode['TSI'])->toBeGreaterThan(0)
        ->and($perKode['TID'])->toBeGreaterThan(0)
        ->and(Mahasiswa::count())->toBeGreaterThan(1000);
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
