<?php

namespace App\Models;

use Database\Factories\PrestasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model prestasi mahasiswa (akademik/non-akademik). Modul pendukung CRUD.
 */
class Prestasi extends Model
{
    /** @use HasFactory<PrestasiFactory> */
    use HasFactory;

    protected $table = 'prestasi';

    protected $fillable = [
        'mahasiswa_id',
        'judul',
        'jenis',
        'tingkat',
        'peringkat',
        'penyelenggara',
        'tanggal',
        'url_bukti',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /**
     * Mahasiswa pemilik prestasi.
     *
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** Label jenis yang ramah-pengguna. */
    public function labelJenis(): string
    {
        return match ($this->jenis) {
            'akademik'     => 'Akademik',
            'non_akademik' => 'Non-akademik',
            default        => ucfirst((string) $this->jenis),
        };
    }

    /** Label tingkat yang ramah-pengguna. */
    public function labelTingkat(): string
    {
        return ucfirst((string) $this->tingkat);
    }
}
