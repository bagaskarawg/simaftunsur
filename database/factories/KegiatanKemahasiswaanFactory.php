<?php

namespace Database\Factories;

use App\Models\KegiatanKemahasiswaan;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KegiatanKemahasiswaan>
 */
class KegiatanKemahasiswaanFactory extends Factory
{
    protected $model = KegiatanKemahasiswaan::class;

    public function definition(): array
    {
        $jenis = fake()->randomElement(['organisasi', 'ukm', 'kepanitiaan', 'seminar']);
        $peran = fake()->randomElement(array_keys((array) config("skkm.kegiatan.{$jenis}")));

        return [
            'mahasiswa_id' => Mahasiswa::factory(),
            'jenis' => $jenis,
            'peran' => $peran,
            'nama_kegiatan' => fake()->randomElement([
                'Himpunan Mahasiswa Teknik Informatika',
                'Panitia Dies Natalis FT',
                'Workshop Data Science',
                'UKM Robotika',
                'Seminar Nasional Teknologi',
            ]),
            'penyelenggara' => fake()->randomElement(['BEM FT UNSUR', 'HMTI', 'Fakultas Teknik', 'Universitas Suryakancana']),
            'periode' => fake()->randomElement(['2024/2025', '2025/2026']),
            'tanggal' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'url_bukti' => fake()->optional()->url(),
            'keterangan' => fake()->optional()->sentence(),
        ];
    }
}
