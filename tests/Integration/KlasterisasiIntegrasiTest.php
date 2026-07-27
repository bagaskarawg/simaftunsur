<?php

use App\Models\KlasterisasiEksekusi;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use App\Services\KlasterisasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function emasIntegrasi(): array
{
    return json_decode(file_get_contents(base_path('tests/fixtures/golden_bab3.json')), true);
}

/** Seed 9 mahasiswa data emas (deret IPK). @return array<string,int> [M1 => id]. */
function seedEmas(ProgramStudi $prodi): array
{
    $peta = [];
    foreach (emasIntegrasi()['mahasiswa'] as $emas) {
        $m = Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);
        foreach ($emas['ipk_per_semester'] as $i => $ipk) {
            $s = $i + 1;
            NilaiIpkSemester::factory()->create([
                'mahasiswa_id' => $m->id, 'semester' => $s, 'ipk' => $ipk,
                'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
            ]);
        }
        $peta[$emas['kode']] = $m->id;
    }

    return $peta;
}

/** Tanggapan service tiruan yang mengembalikan partisi emas. */
function tanggapanEmas(array $petaKodeId): array
{
    $emas = emasIntegrasi();
    $hasil = [];
    $cluster = 0;
    foreach ($emas['partisi_k3'] as $anggota) {
        foreach ($anggota as $kode) {
            $id = $petaKodeId[$kode];
            $hasil[] = ['id' => $id, 'cluster' => $cluster, 'pca_x' => 0.1 * $id, 'pca_y' => -0.1 * $id];
        }
        $cluster++;
    }

    return [
        'k_terpilih' => 3, 'metode_pemilihan_k' => 'otomatis (Elbow — siku WCSS)',
        'fitur_dipakai' => $emas['fitur_akademik'], 'skema_penskalaan' => 'standard',
        'random_state' => 42, 'jumlah_data' => 9,
        'metrik' => ['inertia' => 4.89, 'silhouette' => 0.51, 'davies_bouldin' => 0.6],
        'evaluasi_k' => [
            ['k' => 2, 'inertia' => 10.45, 'silhouette' => 0.56, 'davies_bouldin' => 0.5],
            ['k' => 3, 'inertia' => 4.89, 'silhouette' => 0.51, 'davies_bouldin' => 0.6],
        ],
        'profil_klaster' => [
            ['cluster' => 0, 'jumlah' => 3, 'centroid' => ['ipk_rata_rata' => 3.75], 'label_deskriptif' => 'Berprestasi'],
            ['cluster' => 1, 'jumlah' => 3, 'centroid' => ['ipk_rata_rata' => 3.10], 'label_deskriptif' => 'Menengah'],
            ['cluster' => 2, 'jumlah' => 3, 'centroid' => ['ipk_rata_rata' => 2.50], 'label_deskriptif' => 'Perlu Bimbingan'],
        ],
        'hasil' => $hasil,
        'peringatan' => ['Volume data di bawah ambang minimum.'],
    ];
}

beforeEach(function () {
    $this->prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
});

it('I-01: sehat() true saat service hidup', function () {
    Http::fake(['*/sehat' => Http::response(['status' => 'ok'])]);
    expect(app(KlasterisasiService::class)->sehat())->toBeTrue();
});

it('I-01: sehat() false saat service mati', function () {
    Http::fake(['*/sehat' => Http::response('', 500)]);
    expect(app(KlasterisasiService::class)->sehat())->toBeFalse();
});

it('I-01: service mati ditangani sebagai pesan ramah, bukan error 500', function () {
    seedEmas($this->prodi);
    Http::fake(['*/sehat' => Http::response('', 500)]);
    $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']));

    Volt::test('klasterisasi.index')
        ->call('jalankan')
        ->assertOk()
        ->assertSee('Service klasterisasi tidak aktif');

    expect(KlasterisasiEksekusi::count())->toBe(0);
});

it('I-02: data emas dikirim ke service & partisi hasil dipetakan ke DB', function () {
    $ids = seedEmas($this->prodi);
    Http::fake([
        '*/sehat' => Http::response(['status' => 'ok']),
        '*/klasterisasi' => Http::response(tanggapanEmas($ids)),
    ]);

    $eksekusi = app(KlasterisasiService::class)->jalankan();

    // Payload memuat fitur emas yang dihitung benar (mis. M1 ipk_rata_rata = 3,775).
    Http::assertSent(function ($req) {
        if (! str_contains($req->url(), '/klasterisasi')) {
            return false;
        }

        return collect($req['data'])->pluck('ipk_rata_rata')->contains(fn ($v) => abs($v - 3.775) < 1e-4);
    });

    // Partisi tersimpan = partisi emas (label-invariant).
    $grup = $eksekusi->anggota()->get()->groupBy('cluster')
        ->map(fn ($g) => $g->pluck('mahasiswa_id')->sort()->values()->all())->values()->all();
    $harapan = collect(emasIntegrasi()['partisi_k3'])
        ->map(fn ($a) => collect($a)->map(fn ($k) => $ids[$k])->sort()->values()->all())->values()->all();
    sort($grup);
    sort($harapan);

    expect($grup)->toEqual($harapan);
});

it('I-04: snapshot & metrik tersimpan; riwayat stabil meski IPK berubah', function () {
    $ids = seedEmas($this->prodi);
    Http::fake([
        '*/sehat' => Http::response(['status' => 'ok']),
        '*/klasterisasi' => Http::response(tanggapanEmas($ids)),
    ]);

    $eksekusi = app(KlasterisasiService::class)->jalankan();

    $anggotaM1 = $eksekusi->anggota()->where('mahasiswa_id', $ids['M1'])->first();
    expect($anggotaM1->fitur_nilai['ipk_rata_rata'])->toEqualWithDelta(3.775, 1e-4)
        ->and($eksekusi->silhouette)->toBe(0.51)
        ->and($eksekusi->k_terpilih)->toBe(3);

    // Ubah IPK M1 drastis SETELAH eksekusi tersimpan.
    NilaiIpkSemester::where('mahasiswa_id', $ids['M1'])->update(['ipk' => 1.00]);

    // Snapshot & metrik eksekusi lama TIDAK berubah (riwayat dapat ditelusuri).
    $anggotaM1->refresh();
    expect($anggotaM1->fitur_nilai['ipk_rata_rata'])->toEqualWithDelta(3.775, 1e-4)
        ->and($eksekusi->fresh()->silhouette)->toBe(0.51);
});
