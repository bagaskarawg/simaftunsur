<?php

namespace Database\Factories;

use App\Models\KlasterisasiKategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KlasterisasiKategori>
 */
class KlasterisasiKategoriFactory extends Factory
{
    protected $model = KlasterisasiKategori::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama'        => ucfirst(fake()->unique()->word()),
            'urutan'      => fake()->numberBetween(1, 5),
            'deskripsi'   => fake()->sentence(),
            'rekomendasi' => fake()->sentence(),
            'warna'       => 'cluster-'.fake()->numberBetween(1, 5),
            'aktif'       => true,
        ];
    }
}
