<?php

namespace Database\Factories;

use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramStudi>
 */
class ProgramStudiFactory extends Factory
{
    protected $model = ProgramStudi::class;

    public function definition(): array
    {
        return [
            'kode'    => strtoupper(fake()->unique()->lexify('???')),
            'nama'    => 'Teknik '.fake()->unique()->word(),
            'jenjang' => 'S1',
        ];
    }
}
