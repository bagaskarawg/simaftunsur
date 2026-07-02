<?php

namespace App\Models;

use Database\Factories\PengabdianHibahFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengabdian masyarakat & hibah/PKM — sumber Skor Pengabdian (fitur F7).
 * Modul pendukung CRUD.
 */
class PengabdianHibah extends Model
{
    /** @use HasFactory<PengabdianHibahFactory> */
    use HasFactory;

    protected $table = 'pengabdian_hibah';

    protected $fillable = [
        'mahasiswa_id',
        'jenis',
        'peran',
        'judul',
        'sumber_dana',
        'tahun',
        'url_bukti',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
        ];
    }

    /**
     * Mahasiswa pemilik catatan.
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
            'hibah_didanai'         => 'Hibah/PKM Didanai',
            'proposal_lolos'        => 'Proposal Lolos Seleksi',
            'pengabdian_masyarakat' => 'Pengabdian Masyarakat',
            default                 => ucfirst((string) $this->jenis),
        };
    }

    /** Label peran yang ramah-pengguna. */
    public function labelPeran(): string
    {
        return match ($this->peran) {
            'ketua'         => 'Ketua',
            'anggota'       => 'Anggota',
            'peserta_aktif' => 'Peserta Aktif',
            default         => ucfirst((string) $this->peran),
        };
    }

    /**
     * Poin SKKM untuk catatan ini (fitur F7), dari rubrik config/skkm.php
     * berdasarkan (jenis, peran).
     */
    public function poin(): int
    {
        return (int) config("skkm.pengabdian.{$this->jenis}.{$this->peran}", 0);
    }
}
