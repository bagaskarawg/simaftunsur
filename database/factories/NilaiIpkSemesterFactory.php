<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NilaiIpkSemester>
 */
class NilaiIpkSemesterFactory extends Factory
{
    protected $model = NilaiIpkSemester::class;

    public function definition(): array
    {
        $semester = fake()->numberBetween(1, 8);

        return [
            'mahasiswa_id'           => Mahasiswa::factory(),
            'semester'               => $semester,
            'tahun_akademik'         => '2025/2026',
            'semester_ganjil_genap'  => $semester % 2 === 1 ? 'ganjil' : 'genap',
            'ipk'                    => $this->ipkDistribusiNormal(3.0, 0.4),
            'sks_diambil'            => fake()->numberBetween(18, 24),
            'sks_lulus'              => fake()->numberBetween(15, 24),
        ];
    }

    /**
     * Bangkitkan satu sampel IPK dari distribusi normal (Box-Muller),
     * lalu klip ke rentang valid 1.50-4.00 dan bulatkan ke 2 desimal.
     */
    protected function ipkDistribusiNormal(float $rata, float $simpangan): float
    {
        $u1 = max(mt_rand() / mt_getrandmax(), 1e-9);
        $u2 = mt_rand() / mt_getrandmax();
        $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

        $nilai = $rata + $z * $simpangan;
        $nilai = max(1.50, min(4.00, $nilai));

        return round($nilai, 2);
    }
}
