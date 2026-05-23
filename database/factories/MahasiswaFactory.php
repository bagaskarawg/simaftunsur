<?php

namespace Database\Factories;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Mahasiswa>
 */
class MahasiswaFactory extends Factory
{
    protected $model = Mahasiswa::class;

    /**
     * Cuplikan nama Indonesia agar data dummy realistis.
     *
     * @var array<int, string>
     */
    protected static array $namaDepan = [
        'Budi', 'Siti', 'Ahmad', 'Dewi', 'Rizki', 'Putri', 'Andi', 'Nur', 'Adi', 'Indah',
        'Fajar', 'Yuni', 'Bagas', 'Sari', 'Hendra', 'Lina', 'Galih', 'Maya', 'Reza', 'Tika',
    ];

    /**
     * @var array<int, string>
     */
    protected static array $namaBelakang = [
        'Santoso', 'Wijaya', 'Pratama', 'Lestari', 'Saputra', 'Anggraini', 'Hidayat',
        'Rahmawati', 'Kusuma', 'Permata', 'Setiawan', 'Maharani', 'Nugroho', 'Wulandari',
    ];

    public function definition(): array
    {
        $angkatan = fake()->numberBetween(2019, 2023);
        $jenisKelamin = fake()->randomElement(['L', 'P']);

        return [
            'nim' => fake()->unique()->numerify('20#########'),
            'nama' => fake()->randomElement(self::$namaDepan).' '
                .fake()->randomElement(self::$namaBelakang),
            'program_studi_id' => ProgramStudi::factory(),
            'angkatan' => $angkatan,
            'semester_aktif' => fake()->numberBetween(3, 7),
            'jenis_kelamin' => $jenisKelamin,
            'status' => 'aktif',
            'status_akhir' => null,
            'email' => fake()->unique()->safeEmail(),
            'nomor_telepon' => '08'.fake()->numerify('##########'),
        ];
    }

    /**
     * Ikat mahasiswa pada prodi tertentu.
     */
    public function untukProdi(ProgramStudi $prodi): static
    {
        return $this->state(fn () => ['program_studi_id' => $prodi->id]);
    }

    /**
     * Atur semester aktif eksplisit.
     */
    public function denganSemester(int $semester): static
    {
        return $this->state(fn () => ['semester_aktif' => $semester]);
    }
}
