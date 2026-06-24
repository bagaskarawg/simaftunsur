<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\TracerStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TracerStudy>
 */
class TracerStudyFactory extends Factory
{
    protected $model = TracerStudy::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['bekerja', 'wirausaha', 'lanjut_studi', 'belum_bekerja']);
        $bekerja = in_array($status, ['bekerja', 'wirausaha'], true);

        return [
            'mahasiswa_id'      => Mahasiswa::factory(),
            'tahun_lulus'       => fake()->numberBetween(2020, 2025),
            'status_pekerjaan'  => $status,
            'masa_tunggu_bulan' => $bekerja ? fake()->numberBetween(0, 18) : null,
            'nama_instansi'     => $bekerja ? fake()->company() : null,
            'relevansi'         => $bekerja ? fake()->randomElement(['sangat_relevan', 'relevan', 'kurang_relevan', 'tidak_relevan']) : null,
            'rentang_gaji'      => $bekerja ? fake()->randomElement(['< 3 juta', '3-5 juta', '5-10 juta', '> 10 juta']) : null,
            'tanggal_isi'       => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
