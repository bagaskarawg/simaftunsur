<?php

namespace App\Models;

use Database\Factories\KknPesertaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Peserta KKN — mahasiswa anggota sebuah kelompok, beserta jabatan,
 * status keikutsertaan, dan nilai akhir. Modul pendukung CRUD.
 */
class KknPeserta extends Model
{
    /** @use HasFactory<KknPesertaFactory> */
    use HasFactory;

    protected $table = 'kkn_peserta';

    protected $fillable = [
        'kkn_kelompok_id',
        'mahasiswa_id',
        'jabatan',
        'status',
        'nilai_akhir',
        'nilai_huruf',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'nilai_akhir' => 'decimal:2',
        ];
    }

    /**
     * Kelompok tempat peserta terdaftar.
     *
     * @return BelongsTo<KknKelompok, $this>
     */
    public function kelompok(): BelongsTo
    {
        return $this->belongsTo(KknKelompok::class, 'kkn_kelompok_id');
    }

    /**
     * Mahasiswa peserta.
     *
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** Label jabatan yang ramah-pengguna. */
    public function labelJabatan(): string
    {
        return ucfirst((string) $this->jabatan);
    }

    /** Label status yang ramah-pengguna. */
    public function labelStatus(): string
    {
        return match ($this->status) {
            'terdaftar'         => 'Terdaftar',
            'aktif'             => 'Aktif',
            'selesai'           => 'Selesai',
            'mengundurkan_diri' => 'Mengundurkan Diri',
            default             => ucfirst((string) $this->status),
        };
    }
}
