<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * Peran (role) RBAC — definisi peran & penugasan izinnya, dikelola Administrator
 * lewat UI. Katalog izin yang dapat ditugaskan tetap dari config/peran.izin.
 */
class Peran extends Model
{
    protected $table = 'peran';

    protected $fillable = ['kode', 'nama', 'deskripsi', 'dilindungi', 'wildcard'];

    protected function casts(): array
    {
        return [
            'dilindungi' => 'boolean',
            'wildcard' => 'boolean',
        ];
    }

    /**
     * Baris penugasan izin (satu baris = satu kode izin) untuk peran ini.
     *
     * @return HasMany<IzinPeran, $this>
     */
    public function izin(): HasMany
    {
        return $this->hasMany(IzinPeran::class, 'peran_id');
    }

    /**
     * Daftar kode izin milik peran ini. Peran wildcard → ['*'].
     *
     * @return array<int, string>
     */
    public function daftarKodeIzin(): array
    {
        if ($this->wildcard) {
            return ['*'];
        }

        return $this->izin->pluck('izin_kode')->all();
    }

    /**
     * Peta peran→izin dari DB, di-cache satu request agar cek izin tidak
     * memicu query berulang. Bentuk: ['kode_peran' => ['izin.a', ...]].
     *
     * @return array<string, array<int, string>>
     */
    public static function petaIzin(): array
    {
        return Cache::store('array')->rememberForever('peta_izin_peran', function () {
            return static::query()
                ->with('izin')
                ->get()
                ->mapWithKeys(fn (Peran $p) => [$p->kode => $p->daftarKodeIzin()])
                ->all();
        });
    }

    /** Kosongkan cache peta izin (dipanggil setelah perubahan peran/izin). */
    public static function lupakanCache(): void
    {
        Cache::store('array')->forget('peta_izin_peran');
    }
}
