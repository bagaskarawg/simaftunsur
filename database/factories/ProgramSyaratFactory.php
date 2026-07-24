<?php

namespace Database\Factories;

use App\Models\Program;
use App\Models\ProgramSyarat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProgramSyarat>
 */
class ProgramSyaratFactory extends Factory
{
    protected $model = ProgramSyarat::class;

    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'bidang' => 'ipk_rata_rata',
            'operator' => 'gte',
            'nilai' => '3.00',
            'wajib' => true,
            'label' => 'IPK rata-rata ≥ 3,00',
        ];
    }

    /**
     * Set syarat secara ringkas untuk pengujian.
     *
     * @param  array<int, string>|string  $nilai
     */
    public function kriteria(string $bidang, string $operator, array|string $nilai, bool $wajib = true): static
    {
        $nilaiTersimpan = is_array($nilai) ? json_encode($nilai) : $nilai;

        return $this->state(fn () => [
            'bidang' => $bidang,
            'operator' => $operator,
            'nilai' => $nilaiTersimpan,
            'wajib' => $wajib,
            'label' => "$bidang $operator ".(is_array($nilai) ? implode('/', $nilai) : $nilai),
        ]);
    }

    /** Syarat tidak wajib (informatif). */
    public function opsional(): static
    {
        return $this->state(fn () => ['wajib' => false]);
    }
}
