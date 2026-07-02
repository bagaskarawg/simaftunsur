<?php

namespace App\Models;

use Database\Factories\BeasiswaPenerimaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Penerima beasiswa — menghubungkan mahasiswa dengan satu kategori beasiswa
 * pada periode tertentu, lengkap dengan siklus status usulan→penetapan.
 * Modul pendukung CRUD.
 */
class BeasiswaPenerima extends Model
{
    /** @use HasFactory<BeasiswaPenerimaFactory> */
    use HasFactory;

    protected $table = 'beasiswa_penerima';

    protected $fillable = [
        'mahasiswa_id',
        'beasiswa_kategori_id',
        'tahun_akademik',
        'semester',
        'status',
        'nominal',
        'no_sk',
        'tanggal_sk',
        'sumber_usulan',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'nominal'    => 'decimal:2',
            'tanggal_sk' => 'date',
        ];
    }

    /**
     * Mahasiswa penerima.
     *
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /**
     * Kategori beasiswa yang diterima.
     *
     * @return BelongsTo<BeasiswaKategori, $this>
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(BeasiswaKategori::class, 'beasiswa_kategori_id');
    }

    /** Label status yang ramah-pengguna. */
    public function labelStatus(): string
    {
        return match ($this->status) {
            'diusulkan'    => 'Diusulkan',
            'diverifikasi' => 'Diverifikasi',
            'ditetapkan'   => 'Ditetapkan',
            'ditolak'      => 'Ditolak',
            'selesai'      => 'Selesai',
            'dibekukan'    => 'Dibekukan',
            default        => ucfirst((string) $this->status),
        };
    }

    /** Label periode (semester) yang ramah-pengguna. */
    public function labelSemester(): string
    {
        return ucfirst((string) $this->semester);
    }
}
