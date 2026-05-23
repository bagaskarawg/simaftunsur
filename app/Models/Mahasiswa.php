<?php

namespace App\Models;

use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model mahasiswa — entitas utama subjek klasterisasi K-Means.
 */
class Mahasiswa extends Model
{
    /** @use HasFactory<MahasiswaFactory> */
    use HasFactory;

    protected $table = 'mahasiswa';

    protected $fillable = [
        'npm',
        'nama',
        'program_studi_id',
        'angkatan',
        'semester_aktif',
        'jenis_kelamin',
        'status',
        'status_akhir',
        'email',
        'nomor_telepon',
    ];

    protected function casts(): array
    {
        return [
            'angkatan'       => 'integer',
            'semester_aktif' => 'integer',
        ];
    }

    /**
     * Prodi tempat mahasiswa terdaftar.
     *
     * @return BelongsTo<ProgramStudi, $this>
     */
    public function programStudi(): BelongsTo
    {
        return $this->belongsTo(ProgramStudi::class);
    }

    /**
     * Riwayat IPK per semester, selalu terurut berdasarkan semester
     * agar perhitungan tren/konsistensi konsisten.
     *
     * @return HasMany<NilaiIpkSemester, $this>
     */
    public function nilaiIpkSemester(): HasMany
    {
        return $this->hasMany(NilaiIpkSemester::class)->orderBy('semester');
    }

    /**
     * Rata-rata IPK seluruh semester yang sudah tercatat.
     * Mengembalikan 0.0 jika belum ada catatan.
     */
    public function ipkRataRata(): float
    {
        $nilai = $this->nilaiIpkSemester->pluck('ipk');

        if ($nilai->isEmpty()) {
            return 0.0;
        }

        return round((float) $nilai->avg(), 4);
    }

    /**
     * IPK semester terakhir (semester tertinggi yang tercatat).
     */
    public function ipkTerakhir(): ?float
    {
        $terakhir = $this->nilaiIpkSemester->last();

        return $terakhir ? (float) $terakhir->ipk : null;
    }

    /**
     * Tren IPK — slope regresi linear sederhana terhadap urutan semester.
     *
     * Nilai positif berarti IPK cenderung naik; negatif berarti turun.
     * Mengembalikan 0.0 bila data kurang dari 2 titik (tidak bisa dihitung
     * slope) atau bila seluruh titik berada di semester yang sama.
     */
    public function tren(): float
    {
        $titik = $this->nilaiIpkSemester
            ->map(fn ($baris) => ['x' => (float) $baris->semester, 'y' => (float) $baris->ipk])
            ->values();

        $n = $titik->count();
        if ($n < 2) {
            return 0.0;
        }

        $rataX = $titik->avg('x');
        $rataY = $titik->avg('y');

        $pembilang = 0.0;
        $penyebut = 0.0;
        foreach ($titik as $t) {
            $dx = $t['x'] - $rataX;
            $dy = $t['y'] - $rataY;
            $pembilang += $dx * $dy;
            $penyebut  += $dx * $dx;
        }

        if ($penyebut === 0.0) {
            return 0.0;
        }

        return round($pembilang / $penyebut, 4);
    }

    /**
     * Konsistensi IPK — standar deviasi populasi.
     *
     * Nilai kecil = stabil, besar = fluktuatif.
     */
    public function konsistensi(): float
    {
        $nilai = $this->nilaiIpkSemester->pluck('ipk')->map(fn ($v) => (float) $v);

        $n = $nilai->count();
        if ($n < 2) {
            return 0.0;
        }

        $rata = $nilai->avg();
        $jumlahKuadrat = $nilai->reduce(
            fn ($akumulasi, $v) => $akumulasi + (($v - $rata) ** 2),
            0.0,
        );

        return round(sqrt($jumlahKuadrat / $n), 4);
    }
}
