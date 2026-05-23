<?php

namespace App\Models;

use Database\Factories\NilaiIpkSemesterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model rekam IPK per semester milik seorang mahasiswa.
 */
class NilaiIpkSemester extends Model
{
    /** @use HasFactory<NilaiIpkSemesterFactory> */
    use HasFactory;

    protected $table = 'nilai_ipk_semester';

    protected $fillable = [
        'mahasiswa_id',
        'semester',
        'tahun_akademik',
        'semester_ganjil_genap',
        'ipk',
        'sks_diambil',
        'sks_lulus',
    ];

    protected function casts(): array
    {
        return [
            'semester'    => 'integer',
            'ipk'         => 'decimal:2',
            'sks_diambil' => 'integer',
            'sks_lulus'   => 'integer',
        ];
    }

    /**
     * Mahasiswa pemilik catatan IPK ini.
     *
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
