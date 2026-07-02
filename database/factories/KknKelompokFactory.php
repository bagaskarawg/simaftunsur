<?php

namespace Database\Factories;

use App\Models\KknDpl;
use App\Models\KknKelompok;
use App\Models\KknLokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KknKelompok>
 */
class KknKelompokFactory extends Factory
{
    protected $model = KknKelompok::class;

    public function definition(): array
    {
        return [
            'nama_kelompok'  => 'Kelompok '.fake()->unique()->numberBetween(1, 200),
            'kkn_lokasi_id'  => KknLokasi::factory(),
            'kkn_dpl_id'     => KknDpl::factory(),
            'tahun_akademik' => fake()->randomElement(['2024/2025', '2025/2026']),
            'status'         => fake()->randomElement(['persiapan', 'berjalan', 'selesai']),
            'keterangan'     => fake()->optional()->sentence(),
        ];
    }
}
