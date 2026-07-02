<?php

namespace Database\Factories;

use App\Models\BeasiswaKategori;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BeasiswaKategori>
 */
class BeasiswaKategoriFactory extends Factory
{
    protected $model = BeasiswaKategori::class;

    public function definition(): array
    {
        return [
            'kode'          => strtoupper(fake()->unique()->bothify('BEA-##??')),
            'nama'          => fake()->randomElement([
                'Beasiswa KIP Kuliah',
                'Subsidi UKT Fakultas Teknik',
                'Beasiswa LLDIKTI Wilayah IV',
                'Beasiswa Prestasi Akademik',
            ]),
            'jenis_bantuan' => fake()->randomElement(['ukt', 'biaya_hidup', 'total']),
            'sumber_dana'   => fake()->randomElement(['ftunsur', 'lldikti', 'kemendikti']),
            'aktif'         => true,
            'keterangan'    => fake()->optional()->sentence(),
        ];
    }
}
