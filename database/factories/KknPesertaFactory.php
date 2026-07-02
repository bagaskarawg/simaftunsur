<?php

namespace Database\Factories;

use App\Models\KknKelompok;
use App\Models\KknPeserta;
use App\Models\Mahasiswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KknPeserta>
 */
class KknPesertaFactory extends Factory
{
    protected $model = KknPeserta::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['terdaftar', 'aktif', 'selesai']);
        $sudahNilai = $status === 'selesai';
        $nilai = $sudahNilai ? fake()->randomFloat(2, 60, 95) : null;

        return [
            'kkn_kelompok_id' => KknKelompok::factory(),
            'mahasiswa_id'    => Mahasiswa::factory(),
            'jabatan'         => fake()->randomElement(['ketua', 'sekretaris', 'bendahara', 'anggota', 'anggota', 'anggota']),
            'status'          => $status,
            'nilai_akhir'     => $nilai,
            'nilai_huruf'     => $nilai === null ? null : self::hurufDari($nilai),
            'catatan'         => fake()->optional()->sentence(),
        ];
    }

    /** Konversi nilai angka → huruf sederhana. */
    protected static function hurufDari(float $nilai): string
    {
        return match (true) {
            $nilai >= 85 => 'A',
            $nilai >= 80 => 'A-',
            $nilai >= 75 => 'B+',
            $nilai >= 70 => 'B',
            $nilai >= 65 => 'B-',
            $nilai >= 60 => 'C+',
            default      => 'C',
        };
    }
}
