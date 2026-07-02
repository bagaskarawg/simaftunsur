<?php

namespace App\Models;

use Database\Factories\KknKelompokFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kelompok KKN — ditempatkan di satu lokasi & dibimbing satu DPL.
 * Modul pendukung CRUD.
 */
class KknKelompok extends Model
{
    /** @use HasFactory<KknKelompokFactory> */
    use HasFactory;

    protected $table = 'kkn_kelompok';

    protected $fillable = [
        'nama_kelompok',
        'kkn_lokasi_id',
        'kkn_dpl_id',
        'tahun_akademik',
        'status',
        'keterangan',
    ];

    /**
     * Lokasi penempatan kelompok.
     *
     * @return BelongsTo<KknLokasi, $this>
     */
    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(KknLokasi::class, 'kkn_lokasi_id');
    }

    /**
     * DPL pembimbing (boleh null bila belum ditunjuk).
     *
     * @return BelongsTo<KknDpl, $this>
     */
    public function dpl(): BelongsTo
    {
        return $this->belongsTo(KknDpl::class, 'kkn_dpl_id');
    }

    /**
     * Peserta (mahasiswa) anggota kelompok.
     *
     * @return HasMany<KknPeserta, $this>
     */
    public function peserta(): HasMany
    {
        return $this->hasMany(KknPeserta::class);
    }

    /** Label status yang ramah-pengguna. */
    public function labelStatus(): string
    {
        return match ($this->status) {
            'persiapan' => 'Persiapan',
            'berjalan'  => 'Berjalan',
            'selesai'   => 'Selesai',
            default     => ucfirst((string) $this->status),
        };
    }
}
