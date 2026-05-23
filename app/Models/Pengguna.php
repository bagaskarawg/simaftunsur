<?php

namespace App\Models;

use Database\Factories\PenggunaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Config;
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

    /**
     * Cek apakah pengguna memiliki izin tertentu.
     *
     * Aturan:
     *  - Peran yang punya wildcard '*' otomatis lulus untuk izin apa pun.
     *  - Selain itu, cek kecocokan persis pada daftar izin di config/peran.php.
     */
    public function punyaIzin(string $kode): bool
    {
        $daftar = $this->daftarIzinPeran();

        if (in_array('*', $daftar, true)) {
            return true;
        }

        return in_array($kode, $daftar, true);
    }

    /**
     * Cek apakah peran pengguna termasuk dalam daftar peran yang diberikan.
     *
     * @param  string|array<int, string>  $peran
     */
    public function punyaPeran(string|array $peran): bool
    {
        $kandidat = is_array($peran) ? $peran : [$peran];

        return in_array($this->peran, $kandidat, true);
    }

    /**
     * Daftar lengkap kode izin (sudah resolusi wildcard) milik pengguna.
     *
     * @return array<int, string>
     */
    public function semuaIzin(): array
    {
        $daftar = $this->daftarIzinPeran();

        if (! in_array('*', $daftar, true)) {
            return $daftar;
        }

        // Wildcard → kembalikan gabungan unik seluruh izin yang pernah
        // didefinisikan di config (agar konsumen API dapat melihat
        // cakupan akses admin secara eksplisit).
        $semua = collect((array) Config::get('peran.peta', []))
            ->flatten()
            ->reject(fn ($v) => $v === '*')
            ->unique()
            ->values()
            ->all();

        return $semua;
    }

    /**
     * Daftar izin mentah sesuai konfigurasi untuk peran pengguna.
     *
     * @return array<int, string>
     */
    protected function daftarIzinPeran(): array
    {
        $peta = (array) Config::get('peran.peta', []);

        return array_values((array) ($peta[$this->peran] ?? []));
    }
}
