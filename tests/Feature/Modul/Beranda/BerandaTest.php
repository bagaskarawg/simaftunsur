<?php

use App\Models\KlasterisasiEksekusi;
use App\Models\Pengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

/** Buat satu eksekusi klaster contoh. */
function eksekusiBeranda(): KlasterisasiEksekusi
{
    return KlasterisasiEksekusi::create([
        'k_terpilih'         => 3,
        'metode_pemilihan_k' => 'otomatis (Silhouette tertinggi)',
        'fitur_dipakai'      => ['ipk_rata_rata', 'tren'],
        'skema_penskalaan'   => 'standard',
        'jumlah_data'        => 30,
        'silhouette'         => 0.51,
        'davies_bouldin'     => 0.80,
        'inertia'            => 12.0,
        'evaluasi_k'         => [['k' => 3, 'inertia' => 12.0, 'silhouette' => 0.51, 'davies_bouldin' => 0.80]],
        'profil_klaster'     => [
            ['cluster' => 0, 'jumlah' => 12, 'centroid' => [], 'label_deskriptif' => 'Berprestasi'],
            ['cluster' => 1, 'jumlah' => 18, 'centroid' => [], 'label_deskriptif' => 'Perlu Pembinaan'],
        ],
        'peringatan'         => [],
    ]);
}

it('beranda tampil untuk pengguna terautentikasi', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_prodi']));

    $this->get(route('beranda'))->assertOk()->assertSee('Selamat datang');
});

it('menampilkan ringkasan klaster bagi yang berizin saat ada eksekusi', function () {
    eksekusiBeranda();
    $this->actingAs(Pengguna::factory()->admin()->create());

    $this->get(route('beranda'))
        ->assertOk()
        ->assertSee('Klasterisasi Terkini')
        ->assertSee('Berprestasi');
});

it('menyembunyikan kartu klaster bagi peran tanpa izin klasterisasi.lihat', function () {
    eksekusiBeranda();
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf_prodi'])); // tanpa klasterisasi.lihat

    $this->get(route('beranda'))
        ->assertOk()
        ->assertDontSee('Klasterisasi Terkini');
});
