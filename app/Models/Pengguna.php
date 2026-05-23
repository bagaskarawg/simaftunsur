<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * Model Pengguna — entitas yang dapat masuk ke SIMAFTUNSUR.
 *
 * Identitas masuk memakai NIP/NIDN (kolom `nip`). Kolom kata sandi
 * memakai nama Indonesia `kata_sandi` sehingga perlu override
 * getAuthPasswordName() agar Laravel/Fortify mengenalinya.
 */
class Pengguna extends Authenticatable
{
    /** @use HasFactory<PenggunaFactory> */
    use HasFactory, Notifiable;

    /**
     * Nama tabel di database (override default 'penggunas').
     */
    protected $table = 'pengguna';

    /**
     * Atribut yang boleh diisi massal.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nip',
        'nama',
        'email',
        'kata_sandi',
        'peran',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'kata_sandi',
        'remember_token',
    ];

    /**
     * Casting atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_terverifikasi_pada' => 'datetime',
            'kata_sandi' => 'hashed',
        ];
    }

    /**
     * Memberitahu sistem autentikasi Laravel/Fortify bahwa kolom kata sandi
     * di model ini bernama `kata_sandi`, bukan `password` default.
     */
    public function getAuthPasswordName(): string
    {
        return 'kata_sandi';
    }

    /**
     * Inisial nama untuk avatar (mis. "Budi Santoso" → "BS").
     */
    public function inisial(): string
    {
        return Str::of($this->nama)
            ->explode(' ')
            ->take(2)
            ->map(fn ($kata) => Str::substr($kata, 0, 1))
            ->implode('');
    }

    /**
     * Label peran yang ramah-pengguna untuk ditampilkan di antarmuka.
     */
    public function labelPeran(): string
    {
        return match ($this->peran) {
            'admin'   => 'Administrator',
            'dekan'   => 'Dekan',
            'wd3'     => 'Wakil Dekan III',
            'kaprodi' => 'Ketua Program Studi',
            'staf'    => 'Staf Kemahasiswaan',
            'dosen'   => 'Dosen',
            default   => 'Pengguna',
        };
    }
}
