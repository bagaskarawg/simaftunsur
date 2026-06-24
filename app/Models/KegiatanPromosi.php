<?php

namespace App\Models;

use Database\Factories\KegiatanPromosiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model kegiatan promosi/PMB (versi ringkas). Modul pendukung CRUD.
 */
class KegiatanPromosi extends Model
{
    /** @use HasFactory<KegiatanPromosiFactory> */
    use HasFactory;

    protected $table = 'kegiatan_promosi';

    protected $fillable = [
        'nama_kegiatan',
        'sekolah_target',
        'kota',
        'tanggal',
        'petugas',
        'jumlah_peminat',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal'        => 'date',
            'jumlah_peminat' => 'integer',
        ];
    }
}
