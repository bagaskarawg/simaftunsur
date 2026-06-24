<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Label klaster + koordinat PCA seorang mahasiswa pada satu eksekusi.
 */
class KlasterisasiAnggota extends Model
{
    protected $table = 'klasterisasi_anggota';

    /** Baris anggota terikat pada eksekusi induk; tidak perlu timestamp sendiri. */
    public $timestamps = false;

    protected $fillable = [
        'eksekusi_id',
        'mahasiswa_id',
        'cluster',
        'pca_x',
        'pca_y',
    ];

    protected function casts(): array
    {
        return [
            'cluster' => 'integer',
            'pca_x'   => 'float',
            'pca_y'   => 'float',
        ];
    }

    /**
     * Eksekusi induk.
     *
     * @return BelongsTo<KlasterisasiEksekusi, $this>
     */
    public function eksekusi(): BelongsTo
    {
        return $this->belongsTo(KlasterisasiEksekusi::class, 'eksekusi_id');
    }

    /**
     * Mahasiswa yang diberi label klaster.
     *
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
