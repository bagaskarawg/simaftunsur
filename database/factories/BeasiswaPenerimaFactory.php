<?php

namespace Database\Factories;

use App\Models\BeasiswaKategori;
use App\Models\BeasiswaPenerima;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeasiswaPenerima>
 */
class BeasiswaPenerimaFactory extends Factory
{
    protected $model = BeasiswaPenerima::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            'diusulkan', 'diverifikasi', 'ditetapkan', 'ditolak', 'selesai', 'dibekukan',
        ]);

        // Hanya status yang sudah ditetapkan/selesai yang wajar punya SK & nominal.
        $sudahSk = in_array($status, ['ditetapkan', 'selesai', 'dibekukan'], true);

        return [
            'mahasiswa_id'         => Mahasiswa::factory(),
            'beasiswa_kategori_id' => BeasiswaKategori::factory(),
            'tahun_akademik'       => fake()->randomElement(['2024/2025', '2025/2026']),
            'semester'             => fake()->randomElement(['ganjil', 'genap']),
            'status'               => $status,
            'nominal'              => $sudahSk ? fake()->randomElement([2400000, 3600000, 6000000, 12000000]) : null,
            'no_sk'                => $sudahSk ? 'SK/'.fake()->numberBetween(100, 999).'/FT/'.fake()->numberBetween(2024, 2026) : null,
            'tanggal_sk'           => $sudahSk ? fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d') : null,
            'sumber_usulan'        => fake()->randomElement(['Prodi', 'Fakultas', 'Mandiri']),
            'keterangan'           => fake()->optional()->sentence(),
        ];
    }
}
