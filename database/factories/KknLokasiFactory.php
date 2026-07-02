<?php

namespace Database\Factories;

use App\Models\KknLokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KknLokasi>
 */
class KknLokasiFactory extends Factory
{
    protected $model = KknLokasi::class;

    public function definition(): array
    {
        return [
            'nama'           => 'Desa '.fake()->randomElement(['Sukamaju', 'Cibeber', 'Mekarsari', 'Sindangbarang', 'Cikadu', 'Gekbrong', 'Warungkondang']),
            'kecamatan'      => fake()->randomElement(['Cilaku', 'Cibeber', 'Warungkondang', 'Gekbrong', 'Campaka']),
            'kabupaten'      => 'Cianjur',
            'tahun_akademik' => fake()->randomElement(['2024/2025', '2025/2026']),
            'kuota'          => fake()->numberBetween(8, 15),
            'mitra'          => fake()->optional()->randomElement(['Pemerintah Desa', 'Puskesmas', 'Karang Taruna', 'BUMDes']),
            'aktif'          => true,
            'keterangan'     => fake()->optional()->sentence(),
        ];
    }
}
