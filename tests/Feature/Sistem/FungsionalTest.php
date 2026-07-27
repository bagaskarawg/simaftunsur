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

it('KF-01: login valid berhasil; login salah ditolak', function () {
    $pengguna = Pengguna::factory()->create(); // kata_sandi = rahasia123

    // Kredensial salah → tetap tamu.
    $this->post(route('login.store'), ['nip' => $pengguna->nip, 'password' => 'salah'])
        ->assertSessionHasErrors();
    $this->assertGuest();

    // Kredensial benar → terautentikasi & diarahkan ke beranda.
    $this->post(route('login.store'), ['nip' => $pengguna->nip, 'password' => 'rahasia123'])
        ->assertRedirect(route('beranda', absolute: false));
    $this->assertAuthenticatedAs($pengguna);
});

it('KF-02: akses menu sesuai peran', function () {
    $this->actingAs(Pengguna::factory()->admin()->create())->get(route('pengguna.index'))->assertOk();
    $this->actingAs(Pengguna::factory()->wd3()->create())->get(route('laporan.index'))->assertOk();
    $this->actingAs(Pengguna::factory()->stafProdi()->create())->get(route('pengguna.index'))->assertForbidden();
});

it('KF-03: tambah data mahasiswa (CRUD)', function () {
    $prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $this->actingAs(Pengguna::factory()->admin()->create());

    Volt::test('mahasiswa.baru')
        ->set('npm', '55201191299')
        ->set('nama', 'Mahasiswa Uji')
        ->set('program_studi_id', $prodi->id)
        ->set('angkatan', 2022)
        ->set('semester_aktif', 3)
        ->set('jenis_kelamin', 'L')
        ->set('status', 'aktif')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertRedirect(route('mahasiswa.index'));

    expect(Mahasiswa::where('npm', '55201191299')->exists())->toBeTrue();
});

it('KF-05: klasterisasi dijalankan dengan data layak → hasil tersimpan', function () {
    $prodi = ProgramStudi::create(['kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1']);
    $ids = [];
    foreach (range(1, 3) as $i) {
        $m = Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);
        for ($s = 1; $s <= 3; $s++) {
            NilaiIpkSemester::factory()->create([
                'mahasiswa_id' => $m->id, 'semester' => $s,
                'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
            ]);
        }
        $ids[] = $m->id;
    }

    Http::fake([
        '*/sehat' => Http::response(['status' => 'ok']),
        '*/klasterisasi' => Http::response([
            'k_terpilih' => 2, 'metode_pemilihan_k' => 'otomatis (Elbow — siku WCSS)',
            'fitur_dipakai' => ['ipk_rata_rata', 'ipk_terakhir', 'tren', 'konsistensi'],
            'skema_penskalaan' => 'standard', 'jumlah_data' => 3,
            'metrik' => ['inertia' => 5.0, 'silhouette' => 0.5, 'davies_bouldin' => 0.7],
            'evaluasi_k' => [['k' => 2, 'inertia' => 5.0, 'silhouette' => 0.5, 'davies_bouldin' => 0.7]],
            'profil_klaster' => [['cluster' => 0, 'jumlah' => 3, 'centroid' => ['ipk_rata_rata' => 3.2], 'label_deskriptif' => 'Menengah']],
            'hasil' => array_map(fn ($id, $i) => ['id' => $id, 'cluster' => $i % 2, 'pca_x' => 0.1 * $i, 'pca_y' => 0.0], $ids, array_keys($ids)),
            'peringatan' => ['Volume data di bawah ambang minimum.'],
        ]),
    ]);

    $eksekusi = app(KlasterisasiService::class)->jalankan();

    expect($eksekusi)->toBeInstanceOf(KlasterisasiEksekusi::class)
        ->and($eksekusi->k_terpilih)->toBe(2)
        ->and($eksekusi->anggota()->count())->toBe(3);
});

it('KF-07: dashboard WD III tampil', function () {
    $this->actingAs(Pengguna::factory()->wd3()->create());

    $this->get(route('beranda'))->assertOk()->assertSee('Selamat datang');
});

it('KF-08: unduh laporan (CSV) untuk peran berizin', function () {
    $this->actingAs(Pengguna::factory()->wd3()->create());

    $respons = $this->get(route('laporan.ekspor.prodi'));

    $respons->assertOk();
    expect($respons->headers->get('content-type'))->toContain('text/csv');
});
