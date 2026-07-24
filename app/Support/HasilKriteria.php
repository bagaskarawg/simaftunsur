<?php

namespace App\Support;

/**
 * Hasil evaluasi SATU kriteria persyaratan terhadap seorang mahasiswa.
 *
 * Murni boolean: `lolos` menyatakan apakah kriteria terpenuhi. TIDAK ADA
 * skor, bobot, jarak, atau persentase kecocokan — sesuai batasan metodologi.
 */
class HasilKriteria
{
    public function __construct(
        public readonly string $bidang,
        public readonly string $label,
        public readonly bool $wajib,
        public readonly mixed $nilaiAktual,
        public readonly mixed $ambang,
        public readonly bool $lolos,
        public readonly ?string $keterangan = null,
    ) {}
}
