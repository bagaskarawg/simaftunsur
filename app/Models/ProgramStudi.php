<?php

namespace App\Models;

use Database\Factories\ProgramStudiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model program studi FT UNSUR.
 */
class ProgramStudi extends Model
{
    /** @use HasFactory<ProgramStudiFactory> */
    use HasFactory;

    protected $table = 'program_studi';

    protected $fillable = [
        'kode',
        'nama',
        'jenjang',
    ];

    /**
     * Daftar mahasiswa pada prodi ini.
     *
     * @return HasMany<Mahasiswa, $this>
     */
    public function mahasiswa(): HasMany
    {
        return $this->hasMany(Mahasiswa::class);
    }
}
