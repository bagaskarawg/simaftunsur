<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Header satu kali eksekusi klasterisasi K-Means beserta metrik & profilnya.
 */
class KlasterisasiEksekusi extends Model
{
    protected $table = 'klasterisasi_eksekusi';

    protected $fillable = [
        'k_terpilih',
        'metode_pemilihan_k',
        'fitur_dipakai',
        'skema_penskalaan',
        'random_state',
        'versi_algoritma',
        'kriteria_data',
        'jumlah_data',
        'silhouette',
        'davies_bouldin',
        'inertia',
        'evaluasi_k',
        'profil_klaster',
        'peringatan',
        'dijalankan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'fitur_dipakai'  => 'array',
            'evaluasi_k'     => 'array',
            'profil_klaster' => 'array',
            'peringatan'     => 'array',
            'k_terpilih'     => 'integer',
            'random_state'   => 'integer',
            'jumlah_data'    => 'integer',
            'silhouette'     => 'float',
            'davies_bouldin' => 'float',
            'inertia'        => 'float',
        ];
    }

    /**
     * Anggota (mahasiswa) beserta label klaster pada eksekusi ini.
     *
     * @return HasMany<KlasterisasiAnggota, $this>
     */
    public function anggota(): HasMany
    {
        return $this->hasMany(KlasterisasiAnggota::class, 'eksekusi_id');
    }

    /**
     * Profil tiap klaster (centroid + label) pada eksekusi ini.
     *
     * @return HasMany<KlasterisasiKlaster, $this>
     */
    public function klaster(): HasMany
    {
        return $this->hasMany(KlasterisasiKlaster::class, 'eksekusi_id')->orderBy('cluster');
    }

    /**
     * Pengguna yang menjalankan eksekusi (boleh null bila akun terhapus).
     *
     * @return BelongsTo<Pengguna, $this>
     */
    public function pelaksana(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'dijalankan_oleh');
    }
}
