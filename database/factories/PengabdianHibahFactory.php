<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\PengabdianHibah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengabdianHibah>
 */
class PengabdianHibahFactory extends Factory
{
    protected $model = PengabdianHibah::class;

    public function definition(): array
    {
        $jenis = fake()->randomElement(['pimnas', 'hibah_didanai', 'proposal_lolos', 'pengabdian_masyarakat']);
        $peran = fake()->randomElement(array_keys((array) config("skkm.pengabdian.{$jenis}")));

        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'jenis' => $jenis,
            'peran' => $peran,
            'judul' => fake()->randomElement([
                'PKM-Pengabdian Digitalisasi UMKM Desa',
                'Hibah Riset Sistem Informasi Desa',
                'Pelatihan Literasi Digital Masyarakat',
                'PKM-Kewirausahaan Produk Lokal',
            ]),
            'sumber_dana' => fake()->randomElement(['Kemendikti', 'Fakultas Teknik', 'Mandiri', 'LLDIKTI']),
            'tahun' => fake()->numberBetween(2023, 2026),
            'url_bukti' => fake()->optional()->url(),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
