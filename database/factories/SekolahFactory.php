<?php

namespace Database\Factories;

use App\Models\Sekolah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sekolah>
 */
class SekolahFactory extends Factory
{
    protected $model = Sekolah::class;

    public function definition(): array
    {
        return [
            'nama'     => fake()->randomElement(['SMA Negeri', 'SMK Negeri', 'MA Negeri']).' '.fake()->numberBetween(1, 12).' '.fake()->randomElement(['Cianjur', 'Sukabumi', 'Bogor']),
            'jenjang'  => fake()->randomElement(['SMA', 'SMK', 'MA']),
            'kota'     => fake()->randomElement(['Cianjur', 'Sukabumi', 'Bogor', 'Bandung']),
            'alamat'   => fake()->optional()->streetAddress(),
            'kontak'   => fake()->optional()->numerify('08##########'),
        ];
    }
}
