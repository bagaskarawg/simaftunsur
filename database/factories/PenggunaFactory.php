<?php

namespace Database\Factories;

use App\Models\Pengguna;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pengguna>
 */
class PenggunaFactory extends Factory
{
    protected $model = Pengguna::class;

    /**
     * Kata sandi default untuk seluruh instance factory.
     */
    protected static ?string $kataSandi = null;

    /**
     * Definisi state default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nip' => fake()->unique()->numerify('19##########'),
            'nama' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'kata_sandi' => static::$kataSandi ??= Hash::make('rahasia123'),
            'peran' => 'staf_prodi',
            'email_terverifikasi_pada' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Tandai pengguna sebagai admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $atribut) => ['peran' => 'admin']);
    }

    /**
     * Tandai pengguna sebagai Wakil Dekan III.
     */
    public function wd3(): static
    {
        return $this->state(fn (array $atribut) => ['peran' => 'wd3']);
    }

    /**
     * Tandai pengguna sebagai Staf WD III.
     */
    public function stafWd3(): static
    {
        return $this->state(fn (array $atribut) => ['peran' => 'staf_wd3']);
    }

    /**
     * Tandai pengguna sebagai Ketua Program Studi.
     */
    public function kaprodi(): static
    {
        return $this->state(fn (array $atribut) => ['peran' => 'kaprodi']);
    }

    /**
     * Tandai pengguna sebagai Staf Prodi.
     */
    public function stafProdi(): static
    {
        return $this->state(fn (array $atribut) => ['peran' => 'staf_prodi']);
    }
}
