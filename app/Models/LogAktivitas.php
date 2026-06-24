<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan log aktivitas (jejak audit). Diisi otomatis lewat model events
 * yang didaftarkan di AppServiceProvider, plus event login/logout.
 */
class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    protected $fillable = ['pengguna_id', 'aksi', 'model', 'deskripsi', 'ip'];

    /**
     * Catat satu baris log untuk perubahan sebuah model bisnis.
     */
    public static function catat(Model $model, string $aksi): void
    {
        static::create([
            'pengguna_id' => auth()->id(),
            'aksi'        => $aksi,
            'model'       => class_basename($model),
            'deskripsi'   => static::labelModel($model),
            'ip'          => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /**
     * Catat aktivitas autentikasi (masuk/keluar).
     */
    public static function catatAuth(?Model $pengguna, string $aksi): void
    {
        static::create([
            'pengguna_id' => $pengguna?->getKey(),
            'aksi'        => $aksi,
            'model'       => 'Auth',
            'deskripsi'   => $pengguna?->nama,
            'ip'          => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    /**
     * Ambil label deskriptif dari atribut yang umum (nama/judul/dll.).
     */
    protected static function labelModel(Model $model): string
    {
        foreach (['nama', 'judul', 'nama_kegiatan', 'npm'] as $atribut) {
            if (! empty($model->{$atribut})) {
                return (string) $model->{$atribut};
            }
        }

        return '#'.$model->getKey();
    }

    /**
     * @return BelongsTo<Pengguna, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'pengguna_id');
    }

    /** Label aksi ramah-pengguna. */
    public function labelAksi(): string
    {
        return match ($this->aksi) {
            'dibuat'  => 'Dibuat',
            'diubah'  => 'Diubah',
            'dihapus' => 'Dihapus',
            'masuk'   => 'Masuk',
            'keluar'  => 'Keluar',
            default   => ucfirst($this->aksi),
        };
    }
}
