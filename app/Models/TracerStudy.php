<?php

namespace App\Models;

use Database\Factories\TracerStudyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model tracer study alumni. Modul pendukung CRUD.
 */
class TracerStudy extends Model
{
    /** @use HasFactory<TracerStudyFactory> */
    use HasFactory;

    protected $table = 'tracer_study';

    protected $fillable = [
        'mahasiswa_id',
        'tahun_lulus',
        'status_pekerjaan',
        'masa_tunggu_bulan',
        'nama_instansi',
        'relevansi',
        'rentang_gaji',
        'tanggal_isi',
    ];

    protected function casts(): array
    {
        return [
            'tahun_lulus'       => 'integer',
            'masa_tunggu_bulan' => 'integer',
            'tanggal_isi'       => 'date',
        ];
    }

    /**
     * Alumni (mahasiswa) yang mengisi tracer.
     *
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** Label status pekerjaan yang ramah-pengguna. */
    public function labelStatus(): string
    {
        return match ($this->status_pekerjaan) {
            'bekerja'       => 'Bekerja',
            'wirausaha'     => 'Wirausaha',
            'lanjut_studi'  => 'Lanjut Studi',
            'belum_bekerja' => 'Belum Bekerja',
            default         => ucfirst((string) $this->status_pekerjaan),
        };
    }

    /** Label relevansi yang ramah-pengguna. */
    public function labelRelevansi(): ?string
    {
        return match ($this->relevansi) {
            'sangat_relevan' => 'Sangat Relevan',
            'relevan'        => 'Relevan',
            'kurang_relevan' => 'Kurang Relevan',
            'tidak_relevan'  => 'Tidak Relevan',
            default          => null,
        };
    }
}
