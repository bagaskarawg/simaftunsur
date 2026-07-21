<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Profil satu klaster hasil K-Means pada sebuah eksekusi — bentuk ternormalisasi
 * dari JSON `profil_klaster`. Menyimpan centroid + label sebagai DASAR yang
 * dapat dipertanggungjawabkan untuk penamaan klaster (mis. "Berprestasi").
 */
class KlasterisasiKlaster extends Model
{
    protected $table = 'klasterisasi_klaster';

    protected $fillable = [
        'eksekusi_id',
        'cluster',
        'label_deskriptif',
        'jumlah_anggota',
        'centroid',
        'centroid_terskala',
        'interpretasi',
        'skor_akademik',
        'skor_non_akademik',
        'skor_komposit',
        'ringkasan_profil',
    ];

    protected function casts(): array
    {
        return [
            'cluster'           => 'integer',
            'jumlah_anggota'    => 'integer',
            'centroid'          => 'array',
            'centroid_terskala' => 'array',
            'skor_akademik'     => 'float',
            'skor_non_akademik' => 'float',
            'skor_komposit'     => 'float',
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
     * Anggota (mahasiswa) yang tergabung dalam klaster ini.
     *
     * @return HasMany<KlasterisasiAnggota, $this>
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(KlasterisasiAnggota::class, 'klaster_id');
    }
}
