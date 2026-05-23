<?php

namespace App\Imports;

/**
 * DTO ringan untuk merangkum hasil pemrosesan impor IPK.
 * Disediakan agar komponen Volt dapat menampilkan ringkasan yang seragam
 * (berapa baris ditambah, ditimpa, gagal) tanpa mengandalkan struktur
 * internal koleksi.
 */
class HasilImpor
{
    /**
     * @param  int  $ditambah  Baris baru yang berhasil di-insert.
     * @param  int  $ditimpa   Baris yang menimpa data lama (upsert hit).
     * @param  array<int, array{baris: int, pesan: string}>  $gagal  Daftar baris gagal beserta keterangannya.
     */
    public function __construct(
        public int $ditambah = 0,
        public int $ditimpa = 0,
        public array $gagal = [],
    ) {}

    public function totalDiproses(): int
    {
        return $this->ditambah + $this->ditimpa;
    }

    public function adaKegagalan(): bool
    {
        return count($this->gagal) > 0;
    }
}
