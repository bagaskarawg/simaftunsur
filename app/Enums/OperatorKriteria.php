<?php

namespace App\Enums;

/**
 * Operator perbandingan untuk satu kriteria persyaratan program.
 *
 * Setiap kriteria dievaluasi INDEPENDEN sebagai boolean (lolos/tidak) memakai
 * salah satu operator ini. Tidak ada agregasi/bobot — lihat EvaluatorKelayakan.
 */
enum OperatorKriteria: string
{
    case Gte = 'gte'; // >=
    case Lte = 'lte'; // <=
    case Gt = 'gt';   // >
    case Lt = 'lt';   // <
    case Eq = 'eq';   // =
    case In = 'in';   // termasuk salah satu (nilai JSON array)

    /** Simbol matematis untuk pratinjau kalimat syarat. */
    public function simbol(): string
    {
        return match ($this) {
            self::Gte => '≥',
            self::Lte => '≤',
            self::Gt => '>',
            self::Lt => '<',
            self::Eq => '=',
            self::In => 'salah satu dari',
        };
    }

    /** Label Bahasa Indonesia untuk dropdown. */
    public function label(): string
    {
        return match ($this) {
            self::Gte => 'Minimal (≥)',
            self::Lte => 'Maksimal (≤)',
            self::Gt => 'Lebih dari (>)',
            self::Lt => 'Kurang dari (<)',
            self::Eq => 'Sama dengan (=)',
            self::In => 'Salah satu dari',
        };
    }

    /**
     * Bandingkan nilai aktual mahasiswa terhadap ambang syarat.
     *
     * Perbandingan numerik untuk gte/lte/gt/lt; kesetaraan longgar (string)
     * untuk eq; keanggotaan himpunan untuk in. Nilai aktual null → selalu
     * false (data belum tersedia, ditangani pemanggil).
     *
     * @param  mixed  $aktual  Nilai mahasiswa (mis. IPK, status, kode prodi).
     * @param  mixed  $ambang  Nilai ambang syarat (array untuk `in`).
     */
    public function bandingkan(mixed $aktual, mixed $ambang): bool
    {
        if ($aktual === null) {
            return false;
        }

        return match ($this) {
            self::Gte => (float) $aktual >= (float) $ambang,
            self::Lte => (float) $aktual <= (float) $ambang,
            self::Gt => (float) $aktual > (float) $ambang,
            self::Lt => (float) $aktual < (float) $ambang,
            self::Eq => (string) $aktual === (string) $ambang,
            self::In => in_array((string) $aktual, array_map('strval', (array) $ambang), true),
        };
    }
}
