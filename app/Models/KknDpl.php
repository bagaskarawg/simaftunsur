<?php

namespace App\Models;

use Database\Factories\KknDplFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master Dosen Pembimbing Lapangan (DPL) KKN. Modul pendukung CRUD.
 */
class KknDpl extends Model
{
    /** @use HasFactory<KknDplFactory> */
    use HasFactory;

    protected $table = 'kkn_dpl';

    protected $fillable = [
        'nama',
        'nip',
        'nomor_telepon',
        'bidang_keahlian',
        'aktif',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * Kelompok yang dibimbing DPL ini.
     *
     * @return HasMany<KknKelompok, $this>
     */
    public function kelompok(): HasMany
    {
        return $this->hasMany(KknKelompok::class);
    }
}
