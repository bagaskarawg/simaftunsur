<?php

namespace App\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Program kemahasiswaan (beasiswa, prestasi mahasiswa, dll) yang memiliki
 * sekumpulan persyaratan terstruktur. Menjadi acuan penyaringan kandidat:
 * mahasiswa layak bila memenuhi SELURUH syarat wajib program (boolean AND).
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'program';

    protected $fillable = [
        'nama',
        'jenis',
        'deskripsi',
        'penyelenggara',
        'pendaftaran_mulai',
        'pendaftaran_selesai',
        'kuota',
        'aktif',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'pendaftaran_mulai' => 'date',
            'pendaftaran_selesai' => 'date',
            'kuota' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /** Label Bahasa Indonesia untuk tiap jenis program. */
    public const LABEL_JENIS = [
        'beasiswa' => 'Beasiswa',
        'prestasi_mahasiswa' => 'Prestasi Mahasiswa',
        'lainnya' => 'Lainnya',
    ];

    public function labelJenis(): string
    {
        return self::LABEL_JENIS[$this->jenis] ?? ucfirst((string) $this->jenis);
    }

    /**
     * Persyaratan program.
     *
     * @return HasMany<ProgramSyarat, $this>
     */
    public function syarat(): HasMany
    {
        return $this->hasMany(ProgramSyarat::class);
    }

    /**
     * Syarat wajib saja (ikut penentuan kelayakan).
     *
     * @return HasMany<ProgramSyarat, $this>
     */
    public function syaratWajib(): HasMany
    {
        return $this->syarat()->where('wajib', true);
    }

    /**
     * Pembuat program.
     *
     * @return BelongsTo<Pengguna, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dibuat_oleh');
    }
}
