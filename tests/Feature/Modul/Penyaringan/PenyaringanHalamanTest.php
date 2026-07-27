<?php

use App\Models\KlasterisasiAnggota;
use App\Models\KlasterisasiEksekusi;
use App\Models\KlasterisasiKlaster;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\Program;
use App\Models\ProgramStudi;
use App\Models\ProgramSyarat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function kandidatProdi(): ProgramStudi
{
    return ProgramStudi::firstOrCreate(['kode' => 'TIF'], ['nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
}

function kandidatMhs(float $ipk, array $atribut = []): Mahasiswa
{
    $m = Mahasiswa::factory()->untukProdi(kandidatProdi())->create(array_merge(['status' => 'aktif'], $atribut));
    for ($s = 1; $s <= 4; $s++) {
        NilaiIpkSemester::factory()->create([
            'mahasiswa_id' => $m->id, 'semester' => $s, 'ipk' => $ipk,
            'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
        ]);
    }

    return $m;
}

function programIpkMin(float $min): Program
{
    $program = Program::factory()->create(['aktif' => true]);
    ProgramSyarat::factory()->for($program)->kriteria('ipk_rata_rata', 'gte', number_format($min, 2, '.', ''))->create();

    return $program;
}

it('menolak akses penyaringan bagi staf_prodi', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_prodi']));

    Volt::test('penyaringan.index')->assertStatus(403);
});

it('menampilkan hanya mahasiswa yang memenuhi saat mode audit dimatikan', function () {
    $lolos = kandidatMhs(3.70);
    $gagal = kandidatMhs(2.80); // gagal → hanya tampil di kelompok audit
    $program = programIpkMin(3.00);

    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    Volt::test('penyaringan.index', ['program' => $program->id])
        ->set('audit', false)
        ->assertOk()
        ->assertSee($lolos->nama)
        ->assertDontSee($gagal->nama);
});

it('mode audit aktif secara bawaan', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    Volt::test('penyaringan.index')->assertSet('audit', true);
});

it('audit menyembunyikan yang tidak memenuhi satu pun syarat wajib', function () {
    // Dua syarat wajib; mahasiswa memenuhi salah satu → tampil di audit.
    $sebagian = kandidatMhs(3.70); // IPK lolos, skor kegiatan 0 → gagal syarat kedua
    $nol = kandidatMhs(2.00);      // gagal keduanya → disembunyikan

    $program = Program::factory()->create(['aktif' => true]);
    ProgramSyarat::factory()->for($program)->kriteria('ipk_rata_rata', 'gte', '3.00')->create();
    ProgramSyarat::factory()->for($program)->kriteria('skor_kegiatan', 'gte', '40')->create();

    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    Volt::test('penyaringan.index', ['program' => $program->id])
        ->set('audit', true)
        ->assertSee($sebagian->nama)
        ->assertDontSee($nol->nama);
});

it('ekspor CSV ditolak bagi staf_prodi', function () {
    $program = programIpkMin(3.00);
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_prodi']));

    $this->get(route('penyaringan.ekspor', ['program' => $program->id]))->assertForbidden();
});

it('ekspor CSV berhasil bagi wd3', function () {
    kandidatMhs(3.70);
    $program = programIpkMin(3.00);
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    $response = $this->get(route('penyaringan.ekspor', ['program' => $program->id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});

it('ekspor CSV memuat label klaster asli dari eksekusi terbaru', function () {
    $mhs = kandidatMhs(3.70);
    $program = programIpkMin(3.00);

    // Eksekusi K-Means terbaru dengan label klaster untuk mahasiswa ini.
    $eksekusi = KlasterisasiEksekusi::create([
        'k_terpilih' => 2, 'metode_pemilihan_k' => 'otomatis', 'fitur_dipakai' => ['ipk_rata_rata'],
        'skema_penskalaan' => 'standard', 'jumlah_data' => 1, 'evaluasi_k' => [], 'profil_klaster' => [],
    ]);
    $klaster = KlasterisasiKlaster::create([
        'eksekusi_id' => $eksekusi->id, 'cluster' => 0, 'label_deskriptif' => 'Berprestasi',
        'jumlah_anggota' => 1, 'centroid' => [],
    ]);
    KlasterisasiAnggota::create([
        'eksekusi_id' => $eksekusi->id, 'klaster_id' => $klaster->id,
        'mahasiswa_id' => $mhs->id, 'cluster' => 0,
    ]);

    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));
    $response = $this->get(route('penyaringan.ekspor', ['program' => $program->id]));

    $response->assertOk();
    expect($response->streamedContent())->toContain('Berprestasi');
});
