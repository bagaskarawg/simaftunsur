<?php

namespace Database\Factories;

use App\Models\KknDpl;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KknDpl>
 */
class KknDplFactory extends Factory
{
    protected $model = KknDpl::class;

    public function definition(): array
    {
        return [
            'nama'            => fake()->name().', '.fake()->randomElement(['S.T., M.T.', 'S.Kom., M.Kom.', 'S.T., M.Eng.', 'Dr., S.T., M.T.']),
            'nip'             => (string) fake()->numerify('##################'),
            'nomor_telepon'   => '08'.fake()->numerify('##########'),
            'bidang_keahlian' => fake()->randomElement(['Rekayasa Perangkat Lunak', 'Struktur', 'Manufaktur', 'Sistem Produksi', 'Jaringan Komputer']),
            'aktif'           => true,
        ];
    }
}
