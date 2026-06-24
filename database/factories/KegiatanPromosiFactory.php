<?php

namespace Database\Factories;

use App\Models\KegiatanPromosi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KegiatanPromosi>
 */
class KegiatanPromosiFactory extends Factory
{
    protected $model = KegiatanPromosi::class;

    public function definition(): array
    {
        return [
            'nama_kegiatan'  => fake()->randomElement(['Sosialisasi PMB', 'Campus Expo', 'Roadshow Sekolah', 'Try Out Bersama']),
            'sekolah_target' => 'SMA Negeri '.fake()->numberBetween(1, 12).' Cianjur',
            'kota'           => fake()->randomElement(['Cianjur', 'Sukabumi', 'Bogor', 'Bandung']),
            'tanggal'        => fake()->dateTimeBetween('-1 year', '+2 months')->format('Y-m-d'),
            'petugas'        => fake()->name(),
            'jumlah_peminat' => fake()->optional()->numberBetween(5, 120),
            'catatan'        => fake()->optional()->sentence(),
        ];
    }
}
