<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan sistem (key-value). Akses lewat helper statis ambil()/simpan().
 */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $fillable = ['kunci', 'nilai'];

    /**
     * Ambil nilai pengaturan berdasarkan kunci, atau $default bila belum ada.
     */
    public static function ambil(string $kunci, mixed $default = null): mixed
    {
        return static::query()->where('kunci', $kunci)->value('nilai') ?? $default;
    }

    /**
     * Simpan (upsert) nilai pengaturan.
     */
    public static function simpan(string $kunci, mixed $nilai): void
    {
        static::query()->updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }
}
