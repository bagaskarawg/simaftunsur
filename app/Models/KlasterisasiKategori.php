<?php

namespace App\Models;

use Database\Factories\KlasterisasiKategoriFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Master "Kategori Klaster" — katalog nama/label + rekomendasi pembinaan yang
 * dipetakan ke klaster hasil K-Means menurut peringkat skor komposit.
 *
 * Ini BUKAN penetapan jumlah/isi klaster (jumlah tetap dinamis dari algoritma);
 * hanya katalog penamaan yang dapat dikelola pimpinan. Nama aktif (urut `urutan`)
 * dikirim ke service Python sebagai `konfigurasi_label.katalog`.
 */
class KlasterisasiKategori extends Model
{
    /** @use HasFactory<KlasterisasiKategoriFactory> */
    use HasFactory;

    protected $table = 'klasterisasi_kategori';

    protected $fillable = [
        'nama',
        'urutan',
        'deskripsi',
        'rekomendasi',
        'warna',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'aktif'  => 'boolean',
        ];
    }

    /**
     * Katalog nama kategori aktif, urut peringkat (1 = komposit tertinggi),
     * untuk dikirim ke service Python sebagai daftar label.
     *
     * @return list<string>
     */
    public static function katalogAktif(): array
    {
        return static::query()
            ->where('aktif', true)
            ->orderBy('urutan')
            ->pluck('nama')
            ->all();
    }
}
