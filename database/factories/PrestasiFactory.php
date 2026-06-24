<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\Prestasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prestasi>
 */
class PrestasiFactory extends Factory
{
    protected $model = Prestasi::class;

    public function definition(): array
    {
        $jenis = fake()->randomElement(['akademik', 'non_akademik']);

        return [
            'mahasiswa_id'  => Mahasiswa::factory(),
            'judul'         => 'Juara Lomba '.fake()->randomElement(['Karya Tulis', 'Pemrograman', 'Robotik', 'Debat', 'Futsal']),
            'jenis'         => $jenis,
            'tingkat'       => fake()->randomElement(['lokal', 'regional', 'nasional', 'internasional']),
            'peringkat'     => fake()->randomElement(['Juara 1', 'Juara 2', 'Juara 3', 'Finalis', 'Harapan 1']),
            'penyelenggara' => fake()->randomElement(['Kemendikbudristek', 'BEM FT UNSUR', 'Universitas Suryakancana', 'Himpunan Mahasiswa']),
            'tanggal'       => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'url_bukti'     => fake()->optional()->url(),
        ];
    }
}
