<?php

namespace App\Support;

use App\Models\Mahasiswa;
use Illuminate\Support\Collection;

/**
 * Hasil evaluasi kelayakan seorang mahasiswa terhadap seluruh syarat program.
 *
 * `layak` = konjungsi (AND) seluruh syarat WAJIB. Kriteria opsional ditampilkan
 * informatif dan TIDAK memengaruhi kelayakan. TIDAK ADA properti skor/persentase
 * kecocokan — penyaringan bersifat boolean, keputusan akhir tetap di manusia.
 */
class HasilKelayakan
{
    /** @param array<int, HasilKriteria> $kriteria */
    public function __construct(
        public readonly Mahasiswa $mahasiswa,
        public readonly bool $layak,
        public readonly array $kriteria,
    ) {}

    /** Kriteria wajib saja. */
    public function kriteriaWajib(): Collection
    {
        return collect($this->kriteria)->filter(fn (HasilKriteria $k) => $k->wajib)->values();
    }

    /**
     * Apakah mahasiswa memenuhi SETIDAKNYA satu syarat wajib. Dipakai tampilan
     * untuk menyembunyikan yang tidak memenuhi apa pun — BUKAN sebagai skor,
     * hanya penanda boolean "ada relevansi" (tidak diperingkat).
     */
    public function adaWajibLolos(): bool
    {
        return $this->kriteriaWajib()->contains(fn (HasilKriteria $k) => $k->lolos);
    }
}
