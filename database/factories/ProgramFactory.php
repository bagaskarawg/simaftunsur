<?php

namespace Database\Factories;

use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    public function definition(): array
    {
        return [
            'nama' => 'Program '.fake()->unique()->words(2, true),
            'jenis' => fake()->randomElement(['beasiswa', 'prestasi_mahasiswa', 'lainnya']),
            'deskripsi' => fake()->optional()->sentence(),
            'penyelenggara' => fake()->optional()->company(),
            'pendaftaran_mulai' => null,
            'pendaftaran_selesai' => null,
            'kuota' => fake()->optional()->numberBetween(5, 50),
            'aktif' => true,
            'dibuat_oleh' => null,
        ];
    }

    /** Program tidak aktif. */
    public function nonaktif(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }
}
