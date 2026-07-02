<?php

namespace App\Models;

use Database\Factories\PrestasiFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model prestasi mahasiswa (akademik/non-akademik). Modul pendukung CRUD.
 */
class Prestasi extends Model
{
    /** @use HasFactory<PrestasiFactory> */
    use HasFactory;

    protected $table = 'prestasi';

    protected $fillable = [
        'mahasiswa_id',
        'judul',
        'jenis',
        'tingkat',
        'capaian',
        'peringkat',
        'penyelenggara',
        'tanggal',
        'url_bukti',
        'berkas_bukti',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /**
     * Mahasiswa pemilik prestasi.
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
            'akademik'     => 'Akademik',
            'non_akademik' => 'Non-akademik',
            default        => ucfirst((string) $this->jenis),
        };
    }

    /** Label tingkat yang ramah-pengguna. */
    public function labelTingkat(): string
    {
        return match ($this->tingkat) {
            'lokal'         => 'Universitas/Fakultas',
            'regional'      => 'Provinsi/Regional',
            'nasional'      => 'Nasional',
            'internasional' => 'Internasional',
            default         => ucfirst((string) $this->tingkat),
        };
    }

    /** Label capaian yang ramah-pengguna. */
    public function labelCapaian(): ?string
    {
        return match ($this->capaian) {
            'juara_1' => 'Juara 1',
            'juara_2' => 'Juara 2',
            'juara_3' => 'Juara 3',
            'finalis' => 'Finalis/Peserta',
            default   => null,
        };
    }

    /**
     * Poin SKKM untuk prestasi ini (fitur F5), dari rubrik config/skkm.php
     * berdasarkan (tingkat, capaian). Bernilai 0 bila capaian belum diisi.
     */
    public function poin(): int
    {
        if (! $this->tingkat || ! $this->capaian) {
            return 0;
        }

        return (int) config("skkm.prestasi.{$this->tingkat}.{$this->capaian}", 0);
    }
}
