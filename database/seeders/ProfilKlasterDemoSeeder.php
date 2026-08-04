<?php

namespace Database\Seeders;

use App\Models\KegiatanKemahasiswaan;
use App\Models\Mahasiswa;
use App\Models\PengabdianHibah;
use App\Models\Prestasi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder DEMO profil klasterisasi — membangkitkan data 7 fitur (F1–F7) untuk
 * mahasiswa aktif dengan sebaran 2 DIMENSI: akademik (IPK) × non-akademik
 * (prestasi/kegiatan/pengabdian).
 *
 * ⚠️ DATA SIMULASI — bukan nilai riil (berkas PMB tak memuat IPK maupun catatan
 * SKKM). Tujuannya agar klasterisasi K-Means dapat didemonstrasikan dengan
 * struktur yang bermakna, TERMASUK edge case yang diminta pembimbing:
 *   - IPK tinggi, non-akademik rendah   (akademik kuat, aktivitas minim)
 *   - IPK sedang, non-akademik tinggi   (aktivis, akademik biasa)
 *   - IPK tinggi, non-akademik tinggi   (menonjol di kedua dimensi)
 *   - ...dan seluruh kombinasi 3×3 lain.
 * JANGAN dijadikan bukti kualitas/akurasi klaster (CLAUDE.md §2).
 *
 * Idempoten: IPK di-insertOrIgnore per (mahasiswa_id, semester); bagian
 * non-akademik dilewati bila tabelnya sudah terisi (aman di-seed berulang).
 */
class ProfilKlasterDemoSeeder extends Seeder
{
    /** Batas atas jumlah catatan IPK per mahasiswa. */
    private const MAKS_CATATAN_IPK = 8;

    /**
     * Tahun kalender untuk catatan non-akademik (dipakai untuk kolom tanggal/
     * tahun; plafon SKKM mengelompokkan per grup per tahun).
     */
    private const TAHUN_KEGIATAN = [2024, 2025];

    public function run(): void
    {
        $this->seedEkonomi();
        $this->seedIpk();

        // Non-akademik: hanya sekali (guard) agar tidak menggandakan saat re-seed.
        if (Prestasi::query()->exists()
            || KegiatanKemahasiswaan::query()->exists()
            || PengabdianHibah::query()->exists()) {
            $this->command?->warn('ProfilKlasterDemo: data non-akademik sudah ada — dilewati.');

            return;
        }

        $this->seedNonAkademik();
    }

    /**
     * Profil (tier) dua dimensi berdasarkan id — deterministik & terdistribusi
     * mengikuti matriks 3×3 yang menjamin semua kombinasi (edge case) terisi.
     *
     * @return array{akademik: string, nonakademik: string}
     */
    private function profil(int $id): array
    {
        // Bucket 0–99 menentukan sel matriks (lihat komentar kelas untuk proporsi).
        $b = $id % 100;

        [$aka, $non] = match (true) {
            $b < 8  => ['T', 'T'],   // 8%  IPK tinggi  + non-ak tinggi  (bintang)
            $b < 18 => ['T', 'S'],   // 10% IPK tinggi  + non-ak sedang
            $b < 32 => ['T', 'R'],   // 14% IPK tinggi  + non-ak rendah  (edge)
            $b < 42 => ['S', 'T'],   // 10% IPK sedang  + non-ak tinggi  (edge)
            $b < 58 => ['S', 'S'],   // 16% IPK sedang  + non-ak sedang
            $b < 72 => ['S', 'R'],   // 14% IPK sedang  + non-ak rendah
            $b < 78 => ['R', 'T'],   // 6%  IPK rendah  + non-ak tinggi  (aktivis)
            $b < 88 => ['R', 'S'],   // 10% IPK rendah  + non-ak sedang
            default => ['R', 'R'],   // 12% IPK rendah  + non-ak rendah
        };

        return ['akademik' => $aka, 'nonakademik' => $non];
    }

    /* ===================== BLOK EKONOMI (profil) ===================== */

    /**
     * Isi profil ekonomi/orang tua DEMO untuk seluruh mahasiswa (deterministik
     * per id). Dipakai kriteria Penyaringan Kandidat (mis. beasiswa). Hanya
     * mengisi baris yang masih NULL agar aman di-seed ulang & tak menimpa input
     * manual. ~40% rendah, ~40% menengah, ~20% tinggi.
     */
    private function seedEkonomi(): void
    {
        $jumlah = DB::update(<<<'SQL'
            UPDATE mahasiswa SET
              kategori_ekonomi = CASE
                  WHEN MOD(id,10) < 4 THEN 'rendah'
                  WHEN MOD(id,10) < 8 THEN 'menengah'
                  ELSE 'tinggi' END,
              penghasilan_orang_tua = CASE
                  WHEN MOD(id,10) < 4 THEN 1000000 + MOD(id,16) * 100000
                  WHEN MOD(id,10) < 8 THEN 3000000 + MOD(id,21) * 100000
                  ELSE 7000000 + MOD(id,26) * 200000 END,
              pekerjaan_orang_tua = CASE
                  WHEN MOD(id,10) < 4 THEN 'Buruh/Petani'
                  WHEN MOD(id,10) < 8 THEN 'Wiraswasta'
                  ELSE 'PNS/Profesional' END
            WHERE kategori_ekonomi IS NULL
        SQL);

        $this->command?->info("Profil ekonomi demo: {$jumlah} mahasiswa diisi.");
    }

    /* ===================== BLOK AKADEMIK (F1–F4) ===================== */

    private function seedIpk(): void
    {
        $sekarang = Carbon::now();
        $penyangga = [];
        $totalMhs = 0;
        $totalCatatan = 0;

        Mahasiswa::query()
            ->where('status', 'aktif')
            ->select(['id', 'angkatan', 'semester_aktif'])
            ->orderBy('id')
            ->chunk(500, function ($kelompok) use (&$penyangga, &$totalMhs, &$totalCatatan, $sekarang) {
                foreach ($kelompok as $mhs) {
                    [$dasar, $tren, $derau] = $this->parameterIpk($this->profil($mhs->id)['akademik'], $mhs->id);

                    $jumlah = min(
                        (int) $mhs->semester_aktif,
                        6 + ($mhs->id % 3),
                        self::MAKS_CATATAN_IPK,
                    );
                    if ($jumlah < 1) {
                        continue;
                    }

                    for ($s = 1; $s <= $jumlah; $s++) {
                        $ipk = round(max(1.50, min(4.00, $dasar + $tren * ($s - 1) + $this->derau($derau))), 2);
                        $sksDiambil = 18 + ($mhs->id + $s) % 7;
                        $sksLulus = (int) min($sksDiambil, round($sksDiambil * (0.70 + 0.30 * (($ipk - 1.5) / 2.5))));

                        $penyangga[] = [
                            'mahasiswa_id' => $mhs->id,
                            'semester' => $s,
                            'tahun_akademik' => $this->tahunAkademik((int) $mhs->angkatan, $s),
                            'semester_ganjil_genap' => $s % 2 === 1 ? 'ganjil' : 'genap',
                            'ipk' => $ipk,
                            'sks_diambil' => $sksDiambil,
                            'sks_lulus' => $sksLulus,
                            'created_at' => $sekarang,
                            'updated_at' => $sekarang,
                        ];
                        $totalCatatan++;
                    }
                    $totalMhs++;

                    if (count($penyangga) >= 1000) {
                        DB::table('nilai_ipk_semester')->insertOrIgnore($penyangga);
                        $penyangga = [];
                    }
                }
            });

        if ($penyangga !== []) {
            DB::table('nilai_ipk_semester')->insertOrIgnore($penyangga);
        }

        $this->command?->info("IPK demo: {$totalCatatan} catatan untuk {$totalMhs} mahasiswa aktif.");
    }

    /**
     * @return array{0: float, 1: float, 2: float}  [dasarIpk, tren, simpanganDerau]
     */
    private function parameterIpk(string $tier, int $id): array
    {
        $goyang = (($id % 17) / 17.0 - 0.5) * 0.4;   // ±0.2 variasi antar-mhs

        return match ($tier) {
            'T' => [3.60 + $goyang, 0.020, 0.10],
            'S' => [3.05 + $goyang, 0.005, 0.13],
            default => [2.45 + $goyang, -0.010, 0.15],
        };
    }

    /* =================== BLOK NON-AKADEMIK (F5–F7) =================== */

    private function seedNonAkademik(): void
    {
        $sekarang = Carbon::now();
        $prestasi = [];
        $kegiatan = [];
        $pengabdian = [];
        $counter = ['prestasi' => 0, 'kegiatan' => 0, 'pengabdian' => 0];

        Mahasiswa::query()
            ->where('status', 'aktif')
            ->select(['id'])
            ->orderBy('id')
            ->chunk(500, function ($kelompok) use (&$prestasi, &$kegiatan, &$pengabdian, &$counter, $sekarang) {
                foreach ($kelompok as $mhs) {
                    $tier = $this->profil($mhs->id)['nonakademik'];

                    foreach ($this->catatanPrestasi($tier) as $r) {
                        $prestasi[] = $this->barisPrestasi($mhs->id, $r, $sekarang);
                        $counter['prestasi']++;
                    }
                    foreach ($this->catatanKegiatan($tier) as $r) {
                        $kegiatan[] = $this->barisKegiatan($mhs->id, $r, $sekarang);
                        $counter['kegiatan']++;
                    }
                    foreach ($this->catatanPengabdian($tier) as $r) {
                        $pengabdian[] = $this->barisPengabdian($mhs->id, $r, $sekarang);
                        $counter['pengabdian']++;
                    }

                    if (count($prestasi) >= 800) {
                        DB::table('prestasi')->insert($prestasi);
                        $prestasi = [];
                    }
                    if (count($kegiatan) >= 800) {
                        DB::table('kegiatan_kemahasiswaan')->insert($kegiatan);
                        $kegiatan = [];
                    }
                    if (count($pengabdian) >= 800) {
                        DB::table('pengabdian_hibah')->insert($pengabdian);
                        $pengabdian = [];
                    }
                }
            });

        if ($prestasi !== []) {
            DB::table('prestasi')->insert($prestasi);
        }
        if ($kegiatan !== []) {
            DB::table('kegiatan_kemahasiswaan')->insert($kegiatan);
        }
        if ($pengabdian !== []) {
            DB::table('pengabdian_hibah')->insert($pengabdian);
        }

        $this->command?->info(
            "Non-akademik demo: {$counter['prestasi']} prestasi, {$counter['kegiatan']} kegiatan, "
            ."{$counter['pengabdian']} pengabdian."
        );
    }

    /**
     * Menu catatan prestasi (F5) per tier non-akademik. Tiap item: [tingkat, capaian].
     * Variasi acak menjaga sebaran tidak seragam.
     *
     * @return list<array{0:string,1:string}>
     */
    private function catatanPrestasi(string $tier): array
    {
        return match ($tier) {
            'T' => $this->ambilAcak([
                ['nasional', 'juara_1'], ['nasional', 'juara_2'],
                ['internasional', 'juara_3'], ['regional', 'juara_1'],
            ], mt_rand(1, 2)),
            'S' => $this->ambilAcak([
                ['regional', 'juara_3'], ['lokal', 'juara_1'], ['lokal', 'juara_2'],
            ], mt_rand(1, 1)),
            // Rendah: umumnya nihil; sesekali satu prestasi lokal kecil.
            default => mt_rand(1, 100) <= 20 ? [['lokal', 'finalis']] : [],
        };
    }

    /**
     * Menu catatan kegiatan/organisasi (F6). Tiap item: [jenis, peran].
     *
     * @return list<array{0:string,1:string}>
     */
    private function catatanKegiatan(string $tier): array
    {
        return match ($tier) {
            'T' => $this->ambilAcak([
                ['organisasi', 'ketua_umum'], ['organisasi', 'pengurus_inti'],
                ['kepanitiaan', 'ketua'], ['seminar', 'pembicara'],
            ], mt_rand(2, 3)),
            'S' => $this->ambilAcak([
                ['organisasi', 'anggota_pengurus'], ['kepanitiaan', 'anggota'],
                ['seminar', 'peserta'],
            ], mt_rand(1, 2)),
            default => mt_rand(1, 100) <= 30 ? [['seminar', 'peserta']] : [],
        };
    }

    /**
     * Menu catatan pengabdian/hibah (F7). Tiap item: [jenis, peran].
     *
     * @return list<array{0:string,1:string}>
     */
    private function catatanPengabdian(string $tier): array
    {
        return match ($tier) {
            'T' => $this->ambilAcak([
                ['hibah_didanai', 'ketua'], ['pimnas', 'anggota'], ['proposal_lolos', 'ketua'],
            ], mt_rand(1, 1)),
            'S' => $this->ambilAcak([
                ['proposal_lolos', 'anggota'], ['pengabdian_masyarakat', 'luar_kampus'],
            ], mt_rand(1, 1)),
            default => [],
        };
    }

    /* ===================== PEMBENTUK BARIS ===================== */

    private function barisPrestasi(int $mahasiswaId, array $r, Carbon $now): array
    {
        [$tingkat, $capaian] = $r;
        $tahun = self::TAHUN_KEGIATAN[array_rand(self::TAHUN_KEGIATAN)];

        return [
            'mahasiswa_id' => $mahasiswaId,
            'judul' => 'Kompetisi '.ucfirst($tingkat).' (demo)',
            'jenis' => 'non_akademik',
            'tingkat' => $tingkat,
            'capaian' => $capaian,
            'peringkat' => str_replace('_', ' ', ucfirst($capaian)),
            'penyelenggara' => 'Panitia '.ucfirst($tingkat),
            'tanggal' => Carbon::create($tahun, mt_rand(1, 12), 15),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function barisKegiatan(int $mahasiswaId, array $r, Carbon $now): array
    {
        [$jenis, $peran] = $r;
        $tahun = self::TAHUN_KEGIATAN[array_rand(self::TAHUN_KEGIATAN)];

        return [
            'mahasiswa_id' => $mahasiswaId,
            'jenis' => $jenis,
            'peran' => $peran,
            'nama_kegiatan' => ucfirst($jenis).' (demo)',
            'penyelenggara' => 'FT UNSUR',
            'periode' => $jenis === 'organisasi' ? $tahun.'/'.($tahun + 1) : null,
            'tanggal' => Carbon::create($tahun, mt_rand(1, 12), 15),
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function barisPengabdian(int $mahasiswaId, array $r, Carbon $now): array
    {
        [$jenis, $peran] = $r;
        $tahun = self::TAHUN_KEGIATAN[array_rand(self::TAHUN_KEGIATAN)];

        return [
            'mahasiswa_id' => $mahasiswaId,
            'jenis' => $jenis,
            'peran' => $peran,
            'judul' => ucfirst(str_replace('_', ' ', $jenis)).' (demo)',
            'sumber_dana' => 'Kemendikti',
            'tahun' => $tahun,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /* ===================== UTIL ===================== */

    /**
     * Ambil `$n` elemen acak (tanpa pengembalian) dari `$opsi`.
     *
     * @param  list<array{0:string,1:string}>  $opsi
     * @return list<array{0:string,1:string}>
     */
    private function ambilAcak(array $opsi, int $n): array
    {
        shuffle($opsi);

        return array_slice($opsi, 0, min($n, count($opsi)));
    }

    private function derau(float $simpangan): float
    {
        $u1 = max(mt_rand() / mt_getrandmax(), 1e-9);
        $u2 = mt_rand() / mt_getrandmax();

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2) * $simpangan;
    }

    private function tahunAkademik(int $angkatan, int $semester): string
    {
        $tahun = $angkatan + intdiv($semester - 1, 2);

        return $tahun.'/'.($tahun + 1);
    }
}
