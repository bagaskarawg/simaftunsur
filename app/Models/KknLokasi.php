<?php

namespace App\Models;

use Database\Factories\KknLokasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master lokasi penempatan KKN (desa/kelurahan). Modul pendukung CRUD.
 */
class KknLokasi extends Model
{
    /** @use HasFactory<KknLokasiFactory> */
    use HasFactory;

    protected $table = 'kkn_lokasi';

    protected $fillable = [
        'nama',
        'kecamatan',
        'kabupaten',
        'tahun_akademik',
        'kuota',
        'mitra',
        'aktif',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'kuota' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * Kelompok KKN yang ditempatkan di lokasi ini.
     *
     * @return HasMany<KknKelompok, $this>
     */
    public function kelompok(): HasMany
    {
        return $this->hasMany(KknKelompok::class);
    }

    /** Nama wilayah ringkas: "Desa, Kecamatan, Kabupaten". */
    public function wilayah(): string
    {
        return collect([$this->nama, $this->kecamatan, $this->kabupaten])
            ->filter()
            ->implode(', ');
    }
}
