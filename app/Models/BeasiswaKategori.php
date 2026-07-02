<?php

namespace App\Models;

use Database\Factories\BeasiswaKategoriFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Master kategori/jenis beasiswa. Modul pendukung CRUD.
 */
class BeasiswaKategori extends Model
{
    /** @use HasFactory<BeasiswaKategoriFactory> */
    use HasFactory;

    protected $table = 'beasiswa_kategori';

    protected $fillable = [
        'kode',
        'nama',
        'jenis_bantuan',
        'sumber_dana',
        'aktif',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * Daftar penerima beasiswa pada kategori ini.
     *
     * @return HasMany<BeasiswaPenerima, $this>
     */
    public function penerima(): HasMany
    {
        return $this->hasMany(BeasiswaPenerima::class);
    }

    /** Label jenis bantuan yang ramah-pengguna. */
    public function labelJenisBantuan(): string
    {
        return match ($this->jenis_bantuan) {
            'ukt'         => 'UKT',
            'biaya_hidup' => 'Biaya Hidup',
            'total'       => 'Total (UKT + Biaya Hidup)',
            default       => ucfirst((string) $this->jenis_bantuan),
        };
    }

    /** Label sumber dana yang ramah-pengguna. */
    public function labelSumberDana(): string
    {
        return match ($this->sumber_dana) {
            'ftunsur'    => 'FT UNSUR',
            'lldikti'    => 'LLDIKTI',
            'kemendikti' => 'Kemendikti',
            default      => strtoupper((string) $this->sumber_dana),
        };
    }
}
