<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder DEMO riwayat IPK per semester untuk mahasiswa aktif.
 *
 * ⚠️ DATA SIMULASI — bukan nilai riil. Berkas PMB tidak memuat IPK, sehingga
 * riwayat ini dibangkitkan hanya agar pipeline klasterisasi K-Means dapat
 * didemonstrasikan ujung-ke-ujung. JANGAN dijadikan bukti kualitas/akurasi
 * klaster (lihat CLAUDE.md §2 — jujur soal data sintetik).
 *
 * Strategi pembangkitan: tiap mahasiswa diberi "tier" laten (tinggi/sedang/
 * rendah) berisi IPK dasar, kemiringan tren, dan derau. Nilai per semester =
 * dasar + tren·(semester-1) + derau, diklip ke rentang valid 1.50–4.00.
 * Adanya tier membuat K-Means menemukan struktur klaster yang bermakna,
 * sementara derau menjaga sebaran tetap realistis.
 *
 * Mahasiswa NON-aktif (cuti/non_aktif/lulus/do) sengaja TIDAK diberi IPK →
 * otomatis ter-exclude dari klasterisasi (KlasterisasiService memakai mahasiswa
 * aktif dengan >= 3 catatan IPK).
 */
class NilaiIpkDemoSeeder extends Seeder
{
    /** Batas atas jumlah catatan IPK per mahasiswa agar volume wajar. */
    private const MAKS_CATATAN = 8;

    public function run(): void
    {
        $sekarang = Carbon::now();
        $penyangga = [];
        $totalMahasiswa = 0;
        $totalCatatan = 0;

        Mahasiswa::query()
            ->where('status', 'aktif')
            ->select(['id', 'angkatan', 'semester_aktif'])
            ->orderBy('id')
            ->chunk(500, function ($kelompok) use (&$penyangga, &$totalMahasiswa, &$totalCatatan, $sekarang) {
                foreach ($kelompok as $mahasiswa) {
                    [$dasar, $tren, $derau] = $this->profilTier($mahasiswa->id);

                    // 6–8 catatan, tidak melebihi semester berjalan mahasiswa.
                    $jumlahCatatan = min(
                        (int) $mahasiswa->semester_aktif,
                        6 + ($mahasiswa->id % 3),
                        self::MAKS_CATATAN,
                    );

                    if ($jumlahCatatan < 1) {
                        continue;
                    }

                    for ($semester = 1; $semester <= $jumlahCatatan; $semester++) {
                        $ipk = $dasar + $tren * ($semester - 1) + $this->derauNormal($derau);
                        $ipk = round(max(1.50, min(4.00, $ipk)), 2);

                        $sksDiambil = 18 + ($mahasiswa->id + $semester) % 7;    // 18–24
                        // SKS lulus proporsional terhadap IPK (makin tinggi IPK makin banyak lulus).
                        $sksLulus = (int) min($sksDiambil, round($sksDiambil * (0.70 + 0.30 * (($ipk - 1.5) / 2.5))));

                        $penyangga[] = [
                            'mahasiswa_id' => $mahasiswa->id,
                            'semester' => $semester,
                            'tahun_akademik' => $this->tahunAkademik((int) $mahasiswa->angkatan, $semester),
                            'semester_ganjil_genap' => $semester % 2 === 1 ? 'ganjil' : 'genap',
                            'ipk' => $ipk,
                            'sks_diambil' => $sksDiambil,
                            'sks_lulus' => $sksLulus,
                            'created_at' => $sekarang,
                            'updated_at' => $sekarang,
                        ];
                        $totalCatatan++;
                    }

                    $totalMahasiswa++;

                    if (count($penyangga) >= 1000) {
                        DB::table('nilai_ipk_semester')->insertOrIgnore($penyangga);
                        $penyangga = [];
                    }
                }
            });

        if ($penyangga !== []) {
            DB::table('nilai_ipk_semester')->insertOrIgnore($penyangga);
        }

        $this->command?->info(
            "IPK demo ter-seed: {$totalCatatan} catatan untuk {$totalMahasiswa} mahasiswa aktif."
        );
    }

    /**
     * Tier laten berdasarkan id (deterministik & terdistribusi):
     * ~30% tinggi, ~50% sedang, ~20% rendah.
     *
     * @return array{0: float, 1: float, 2: float}  [ipkDasar, tren, simpanganDerau]
     */
    private function profilTier(int $id): array
    {
        $bucket = $id % 10;

        // Sedikit variasi dasar antar-mahasiswa dalam tier yang sama.
        $goyang = (($id % 17) / 17.0 - 0.5) * 0.4;   // ±0.2

        if ($bucket <= 2) {                          // tinggi (0,1,2)
            return [3.50 + $goyang, 0.025, 0.08];
        }

        if ($bucket <= 7) {                          // sedang (3–7)
            return [3.05 + $goyang, 0.005, 0.12];
        }

        return [2.50 + $goyang, -0.015, 0.16];       // rendah (8,9)
    }

    /**
     * Satu sampel derau dari distribusi normal (Box-Muller), rata-rata 0.
     */
    private function derauNormal(float $simpangan): float
    {
        $u1 = max(mt_rand() / mt_getrandmax(), 1e-9);
        $u2 = mt_rand() / mt_getrandmax();
        $z = sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);

        return $z * $simpangan;
    }

    /**
     * Tahun akademik "YYYY/YYYY" untuk semester tertentu relatif angkatan.
     * Tiap tahun akademik = 2 semester (ganjil lalu genap).
     */
    private function tahunAkademik(int $angkatan, int $semester): string
    {
        $tahun = $angkatan + intdiv($semester - 1, 2);

        return $tahun.'/'.($tahun + 1);
    }
}
