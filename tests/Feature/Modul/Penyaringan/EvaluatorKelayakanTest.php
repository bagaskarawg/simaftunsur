<?php

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Prestasi;
use App\Models\Program;
use App\Models\ProgramStudi;
use App\Models\ProgramSyarat;
use App\Services\EvaluatorKelayakan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function prodiUji(): ProgramStudi
{
    return ProgramStudi::firstOrCreate(
        ['kode' => 'TIF'],
        ['nama' => 'Teknik Informatika', 'jenjang' => 'S1'],
    );
}

/** Mahasiswa aktif dengan IPK seragam di sejumlah semester. */
function mhsIpk(float $ipk, int $semester = 4, array $atribut = []): Mahasiswa
{
    $mahasiswa = Mahasiswa::factory()->untukProdi(prodiUji())
        ->create(array_merge(['status' => 'aktif'], $atribut));

    for ($s = 1; $s <= $semester; $s++) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $mahasiswa->id,
            'semester' => $s,
            'ipk' => $ipk,
            'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }

    return $mahasiswa->fresh();
}

function programDenganSyarat(array $syarat, string $jenis = 'beasiswa'): Program
{
    $program = Program::factory()->create(['jenis' => $jenis]);
    foreach ($syarat as $s) {
        ProgramSyarat::factory()->for($program)->kriteria(
            $s['bidang'], $s['operator'], $s['nilai'], $s['wajib'] ?? true,
        )->create();
    }

    return $program->fresh('syarat');
}

beforeEach(fn () => prodiUji());

it('gte tepat di ambang dianggap lolos (batas operator)', function () {
    $mhs = mhsIpk(3.00);
    $program = programDenganSyarat([
        ['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '3.00'],
    ]);

    $hasil = app(EvaluatorKelayakan::class)->evaluateStudent($program, $mhs);

    expect($hasil[0]->lolos)->toBeTrue();
});

it('gt tepat di ambang dianggap tidak lolos (batas operator)', function () {
    $mhs = mhsIpk(3.00);
    $program = programDenganSyarat([
        ['bidang' => 'ipk_rata_rata', 'operator' => 'gt', 'nilai' => '3.00'],
    ]);

    $hasil = app(EvaluatorKelayakan::class)->evaluateStudent($program, $mhs);

    expect($hasil[0]->lolos)->toBeFalse();
});

it('operator in mengevaluasi keanggotaan himpunan', function () {
    $aktif = mhsIpk(3.2, 4, ['status' => 'aktif']);
    $cuti = mhsIpk(3.2, 4, ['status' => 'cuti']);
    $program = programDenganSyarat([
        ['bidang' => 'status', 'operator' => 'in', 'nilai' => ['aktif', 'lulus']],
    ]);

    $evaluator = app(EvaluatorKelayakan::class);
    expect($evaluator->evaluateStudent($program, $aktif)[0]->lolos)->toBeTrue()
        ->and($evaluator->evaluateStudent($program, $cuti)[0]->lolos)->toBeFalse();
});

it('kriteria jumlah prestasi minimal tingkat menghitung tingkat >= target', function () {
    $mhs = mhsIpk(3.5);
    Prestasi::factory()->create(['mahasiswa_id' => $mhs->id, 'tingkat' => 'internasional', 'capaian' => 'juara_2']);
    $mhs = $mhs->fresh(['prestasi']);

    $program = programDenganSyarat([[
        'bidang' => 'jumlah_prestasi_min_tingkat', 'operator' => 'gte',
        'nilai' => ['tingkat' => 'nasional', 'min_jumlah' => 1],
    ]]);

    // Internasional (rank 4) >= nasional (rank 3) → memenuhi.
    $hasil = app(EvaluatorKelayakan::class)->evaluateStudent($program, $mhs);
    expect($hasil[0]->lolos)->toBeTrue();
});

it('prestasi di bawah tingkat target tidak dihitung', function () {
    $mhs = mhsIpk(3.5);
    Prestasi::factory()->create(['mahasiswa_id' => $mhs->id, 'tingkat' => 'lokal', 'capaian' => 'juara_1']);
    $mhs = $mhs->fresh(['prestasi']);

    $program = programDenganSyarat([[
        'bidang' => 'jumlah_prestasi_min_tingkat', 'operator' => 'gte',
        'nilai' => ['tingkat' => 'nasional', 'min_jumlah' => 1],
    ]]);

    expect(app(EvaluatorKelayakan::class)->evaluateStudent($program, $mhs)[0]->lolos)->toBeFalse();
});

it('mahasiswa belum diklaster: kriteria label klaster tidak lolos dengan keterangan', function () {
    $mhs = mhsIpk(3.8); // tidak ada eksekusi klasterisasi sama sekali
    $program = programDenganSyarat([
        ['bidang' => 'label_klaster', 'operator' => 'eq', 'nilai' => 'Berprestasi'],
    ]);

    $hasil = app(EvaluatorKelayakan::class)->evaluateStudent($program, $mhs)[0];

    expect($hasil->lolos)->toBeFalse()
        ->and($hasil->keterangan)->toBe('belum diklaster');
});

it('data IPK terakhir kosong ditandai belum tersedia, tidak lolos', function () {
    $mhs = Mahasiswa::factory()->untukProdi(prodiUji())->create(['status' => 'aktif']); // tanpa IPK
    $program = programDenganSyarat([
        ['bidang' => 'ipk_terakhir', 'operator' => 'gte', 'nilai' => '3.00'],
    ]);

    $hasil = app(EvaluatorKelayakan::class)->evaluateStudent($program, $mhs)[0];

    expect($hasil->lolos)->toBeFalse()
        ->and($hasil->keterangan)->toBe('data belum tersedia');
});

it('kelayakan adalah AND seluruh syarat wajib', function () {
    $mhs = mhsIpk(3.60); // IPK lolos, tapi skor kegiatan 0 → tidak lolos syarat kedua
    $program = programDenganSyarat([
        ['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '3.00'],
        ['bidang' => 'skor_kegiatan', 'operator' => 'gte', 'nilai' => '40'],
    ]);

    $hasil = app(EvaluatorKelayakan::class)->evaluateProgram($program, collect([$mhs]));

    expect($hasil->first()->layak)->toBeFalse();
});

it('syarat opsional tidak memengaruhi kelayakan', function () {
    $mhs = mhsIpk(3.60);
    $program = programDenganSyarat([
        ['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '3.00', 'wajib' => true],
        ['bidang' => 'skor_kegiatan', 'operator' => 'gte', 'nilai' => '40', 'wajib' => false],
    ]);

    expect(app(EvaluatorKelayakan::class)->evaluateProgram($program, collect([$mhs]))->first()->layak)->toBeTrue();
});

it('program tanpa syarat wajib menganggap semua mahasiswa layak', function () {
    $mhs = mhsIpk(2.10);
    $program = programDenganSyarat([
        ['bidang' => 'skor_prestasi', 'operator' => 'gte', 'nilai' => '10', 'wajib' => false],
    ]);

    // Tidak ada syarat wajib → every() atas himpunan kosong = true.
    expect(app(EvaluatorKelayakan::class)->evaluateProgram($program, collect([$mhs]))->first()->layak)->toBeTrue();
});

it('kandidatQuery mempersempit lewat WHERE untuk field query-able', function () {
    mhsIpk(3.2, 4, ['status' => 'aktif']);
    mhsIpk(3.2, 4, ['status' => 'cuti']);
    $program = programDenganSyarat([
        ['bidang' => 'status', 'operator' => 'eq', 'nilai' => 'aktif'],
    ]);

    $kandidat = app(EvaluatorKelayakan::class)->kandidatQuery($program)->get();

    expect($kandidat)->toHaveCount(1)
        ->and($kandidat->first()->status)->toBe('aktif');
});
