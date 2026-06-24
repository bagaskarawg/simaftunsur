<?php

use App\Models\KlasterisasiAnggota;
use App\Models\KlasterisasiEksekusi;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->prodi = ProgramStudi::create([
        'kode' => 'TIF', 'nama' => 'Teknik Informatika', 'jenjang' => 'S1',
    ]);
});

/**
 * Buat satu eksekusi lengkap dengan satu anggota agar cabang dashboard
 * (KPI, scatter SVG, grafik evaluasi, profil, daftar anggota) ikut terender.
 */
function eksekusiContoh(ProgramStudi $prodi): KlasterisasiEksekusi
{
    $mahasiswa = Mahasiswa::factory()->untukProdi($prodi)->create(['status' => 'aktif']);
    NilaiIpkSemester::factory()->create([
        'mahasiswa_id' => $mahasiswa->id, 'semester' => 1, 'semester_ganjil_genap' => 'ganjil',
    ]);

    $eksekusi = KlasterisasiEksekusi::create([
        'k_terpilih'         => 2,
        'metode_pemilihan_k' => 'otomatis (Silhouette tertinggi)',
        'fitur_dipakai'      => ['ipk_rata_rata', 'tren', 'konsistensi'],
        'skema_penskalaan'   => 'standard',
        'jumlah_data'        => 1,
        'silhouette'         => 0.52,
        'davies_bouldin'     => 0.74,
        'inertia'            => 10.0,
        'evaluasi_k'         => [
            ['k' => 2, 'inertia' => 10.0, 'silhouette' => 0.52, 'davies_bouldin' => 0.74],
            ['k' => 3, 'inertia' => 7.0, 'silhouette' => 0.40, 'davies_bouldin' => 0.95],
        ],
        'profil_klaster'     => [
            ['cluster' => 0, 'jumlah' => 1, 'centroid' => ['ipk_rata_rata' => 3.5, 'tren' => 0.05, 'konsistensi' => 0.1, 'semester_aktif' => 5], 'label_deskriptif' => 'Berprestasi'],
            ['cluster' => 1, 'jumlah' => 1, 'centroid' => ['ipk_rata_rata' => 2.6, 'tren' => -0.03, 'konsistensi' => 0.3, 'semester_aktif' => 5], 'label_deskriptif' => 'Perlu Pembinaan'],
        ],
        'peringatan'         => ['Volume data di bawah ambang minimum.'],
    ]);

    KlasterisasiAnggota::create([
        'eksekusi_id' => $eksekusi->id, 'mahasiswa_id' => $mahasiswa->id,
        'cluster' => 0, 'pca_x' => 0.12, 'pca_y' => -0.08,
    ]);

    return $eksekusi;
}

it('menolak akses tanpa izin klasterisasi.lihat', function () {
    // Peran 'staf' tidak punya klasterisasi.lihat.
    $this->actingAs(Pengguna::factory()->create(['peran' => 'staf']));

    Volt::test('klasterisasi.index')->assertForbidden();
});

it('merender dashboard lengkap dengan hasil eksekusi', function () {
    eksekusiContoh($this->prodi);
    $this->actingAs(Pengguna::factory()->create(['peran' => 'admin']));

    Volt::test('klasterisasi.index')
        ->assertOk()
        ->assertSee('Sebaran Klaster')
        ->assertSee('Berprestasi')
        ->assertSee('Rekomendasi')
        ->assertSee('Catatan validitas')
        ->assertSee('Perbandingan Profil Antar-Klaster') // kartu radar
        ->assertSee('IPK Rata');                          // label sumbu radar
});

it('menampilkan empty state saat belum ada eksekusi', function () {
    $this->actingAs(Pengguna::factory()->create(['peran' => 'wd3']));

    Volt::test('klasterisasi.index')
        ->assertOk()
        ->assertSee('Belum ada hasil klasterisasi')
        ->assertSee('Kesiapan Data Klasterisasi')   // validasi volume selalu tampil
        ->assertSee('Belum memadai');               // <100 mahasiswa layak
});
