<?php

namespace App\Models;

use Database\Factories\KegiatanKemahasiswaanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kegiatan & organisasi kemahasiswaan (organisasi/kepanitiaan/seminar) —
 * sumber Skor Kegiatan (fitur F6). Modul pendukung CRUD.
 */
class KegiatanKemahasiswaan extends Model
{
    /** @use HasFactory<KegiatanKemahasiswaanFactory> */
    use HasFactory;

    protected $table = 'kegiatan_kemahasiswaan';

    protected $fillable = [
        'mahasiswa_id',
        'jenis',
        'peran',
        'nama_kegiatan',
        'penyelenggara',
        'periode',
        'tanggal',
        'url_bukti',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /**
     * Mahasiswa pemilik kegiatan.
     *
     * @return BelongsTo<Mahasiswa, $this>
     */
    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    /** Label jenis yang ramah-pengguna. */
    public function labelJenis(): string
    {
        return match ($this->jenis) {
            'organisasi'  => 'Organisasi/UKM',
            'kepanitiaan' => 'Kepanitiaan',
            'seminar'     => 'Seminar/Workshop',
            default       => ucfirst((string) $this->jenis),
        };
    }

    /** Label peran yang ramah-pengguna. */
    public function labelPeran(): string
    {
        return match ($this->peran) {
            'ketua'         => 'Ketua',
            'wakil'         => 'Wakil',
            'pengurus_inti' => 'Pengurus Inti',
            'anggota'       => 'Anggota',
            'koordinator'   => 'Koordinator',
            'pembicara'     => 'Pembicara',
            'peserta'       => 'Peserta',
            default         => ucfirst((string) $this->peran),
        };
    }

    /**
     * Poin SKKM untuk kegiatan ini (fitur F6), dari rubrik config/skkm.php
     * berdasarkan (jenis, peran).
     */
    public function poin(): int
    {
        return (int) config("skkm.kegiatan.{$this->jenis}.{$this->peran}", 0);
    }
}
