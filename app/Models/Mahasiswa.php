<?php

namespace App\Models;

use Database\Factories\MahasiswaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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
            'angkatan' => 'integer',
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
     * Daftar prestasi mahasiswa (modul pendukung), terbaru lebih dulu.
     *
     * @return HasMany<Prestasi, $this>
     */
    public function prestasi(): HasMany
    {
        return $this->hasMany(Prestasi::class)->latest('tanggal');
    }

    /**
     * Riwayat pengisian tracer study sebagai alumni (modul pendukung),
     * terbaru lebih dulu.
     *
     * @return HasMany<TracerStudy, $this>
     */
    public function tracerStudy(): HasMany
    {
        return $this->hasMany(TracerStudy::class)->latest('tahun_lulus');
    }

    /**
     * Riwayat keanggotaan klaster pada setiap eksekusi K-Means yang
     * pernah menyertakan mahasiswa ini, eksekusi terbaru lebih dulu.
     *
     * @return HasMany<KlasterisasiAnggota, $this>
     */
    public function klasterisasiAnggota(): HasMany
    {
        return $this->hasMany(KlasterisasiAnggota::class)->latest('eksekusi_id');
    }

    /**
     * Riwayat penerimaan beasiswa mahasiswa (modul pendukung), terbaru dulu.
     *
     * @return HasMany<BeasiswaPenerima, $this>
     */
    public function beasiswaPenerima(): HasMany
    {
        return $this->hasMany(BeasiswaPenerima::class)->latest();
    }

    /**
     * Riwayat keikutsertaan KKN mahasiswa (modul pendukung), terbaru dulu.
     *
     * @return HasMany<KknPeserta, $this>
     */
    public function kknPeserta(): HasMany
    {
        return $this->hasMany(KknPeserta::class)->latest();
    }

    /**
     * Kegiatan & organisasi kemahasiswaan (sumber fitur F6), terbaru dulu.
     *
     * @return HasMany<KegiatanKemahasiswaan, $this>
     */
    public function kegiatanKemahasiswaan(): HasMany
    {
        return $this->hasMany(KegiatanKemahasiswaan::class)->latest('tanggal');
    }

    /**
     * Pengabdian masyarakat & hibah/PKM (sumber fitur F7), terbaru dulu.
     *
     * @return HasMany<PengabdianHibah, $this>
     */
    public function pengabdianHibah(): HasMany
    {
        return $this->hasMany(PengabdianHibah::class)->latest('tahun');
    }

    /**
     * Skor Prestasi (fitur F5) — total poin SKKM dengan PLAFON per tingkat/tahun.
     */
    public function skorPrestasi(): int
    {
        return $this->totalPoinBerplafon(
            $this->prestasi,
            fn (Prestasi $p) => $p->tingkat.'|'.($p->tanggal?->year ?? 0),
        );
    }

    /**
     * Skor Kegiatan & Organisasi (fitur F6) — total poin dengan PLAFON per
     * jenis kegiatan/tahun.
     */
    public function skorKegiatan(): int
    {
        return $this->totalPoinBerplafon(
            $this->kegiatanKemahasiswaan,
            fn (KegiatanKemahasiswaan $k) => $k->jenis.'|'.($k->tanggal?->year ?? 0),
        );
    }

    /**
     * Skor Pengabdian & Hibah (fitur F7) — total poin dengan PLAFON per jenis/tahun.
     */
    public function skorPengabdian(): int
    {
        return $this->totalPoinBerplafon(
            $this->pengabdianHibah,
            fn (PengabdianHibah $p) => $p->jenis.'|'.($p->tahun ?? 0),
        );
    }

    /**
     * Akumulasi poin dengan PLAFON: hanya `skkm.plafon_per_grup` item ber-poin
     * TERTINGGI yang dihitung per grup per tahun kalender (rubrik SKKM). Item
     * bernilai 0 tetap dijumlah (tidak berpengaruh). `$kunciGrup` menghasilkan
     * kunci "grup|tahun" untuk tiap catatan.
     *
     * @param  Collection<int, Model>  $items
     */
    private function totalPoinBerplafon($items, callable $kunciGrup): int
    {
        $plafon = (int) config('skkm.plafon_per_grup', 3);

        return (int) $items
            ->groupBy($kunciGrup)
            ->sum(fn ($grup) => $grup
                ->sortByDesc(fn ($item) => $item->poin())
                ->take($plafon)
                ->sum(fn ($item) => $item->poin()));
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
            $penyebut += $dx * $dx;
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
