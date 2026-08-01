<?php

namespace Database\Seeders;

use App\Models\ProgramStudi;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder daftar mahasiswa Teknik (roster nyata) hasil ekstraksi berkas PMB
 * FT UNSUR 2019–2023. Sumber data: `database/data/mahasiswa_teknik.csv`.
 *
 * Catatan penting tentang keterbatasan data (jujur untuk dokumentasi TA):
 *  - Berkas PMB adalah data PENDAFTARAN, sehingga TIDAK memuat IPK per semester.
 *    Karena itu roster ini SENGAJA tanpa riwayat `nilai_ipk_semester`. Mahasiswa
 *    tanpa IPK otomatis tidak memenuhi syarat fitur klasterisasi (butuh IPK)
 *    dan akan ter-exclude — sesuai skenario "data belum cukup".
 *  - `program_studi`, `angkatan`, dan `jenis_kelamin` diturunkan dari NPM
 *    (prefix prodi + dua digit angkatan); jenis kelamin yang tidak ada di sumber
 *    ditebak dari pola nama Indonesia (perkiraan, bukan data resmi).
 *  - `semester_aktif` dihitung relatif terhadap tahun ajaran acuan
 *    (lihat konstanta TAHUN_AJARAN_ACUAN). Ubah satu baris ini bila ingin
 *    memundurkan acuan (mis. ke 2023) agar angkatan termuda menjadi < 3 semester
 *    untuk mendemokan eksklusi.
 *
 * Cohort demo klasterisasi (30 mahasiswa dummy BER-IPK) tetap dibuat terpisah
 * oleh DatabaseSeeder::seedMahasiswaDanIpk() dan tidak diganggu seeder ini.
 */
class MahasiswaTeknikSeeder extends Seeder
{
    /**
     * Tahun ajaran acuan untuk menghitung `semester_aktif` dari angkatan.
     * (Nilai ini HARUS sama dengan yang dipakai saat membangkitkan CSV.)
     */
    private const TAHUN_AJARAN_ACUAN = 2026;

    public function run(): void
    {
        $berkas = database_path('data/mahasiswa_teknik.csv');

        if (! is_file($berkas)) {
            $this->command?->warn("Lewati MahasiswaTeknik: berkas tidak ditemukan ({$berkas}).");

            return;
        }

        // Peta kode prodi -> id (mis. 'TIF' => 1). ProgramStudi wajib sudah di-seed.
        $petaProdi = ProgramStudi::query()->pluck('id', 'kode');

        $pegangan = fopen($berkas, 'r');
        $judul = fgetcsv($pegangan);          // baris header
        $sekarang = Carbon::now();
        $baris = [];
        $dilewati = 0;
        $total = 0;

        while (($data = fgetcsv($pegangan)) !== false) {
            if ($data === [null] || $data === []) {
                continue;
            }

            $rekaman = array_combine($judul, $data);
            $prodiId = $petaProdi[$rekaman['kode_prodi']] ?? null;

            // Kode prodi tak dikenal (mis. tidak ada di master) -> lewati dengan aman.
            if ($prodiId === null) {
                $dilewati++;

                continue;
            }

            $baris[] = [
                'npm' => $rekaman['npm'],
                'nama' => $rekaman['nama'],
                'program_studi_id' => $prodiId,
                'angkatan' => (int) $rekaman['angkatan'],
                'semester_aktif' => (int) $rekaman['semester_aktif'],
                'jenis_kelamin' => $rekaman['jenis_kelamin'],
                'status' => $rekaman['status'],
                'status_akhir' => null,
                'email' => null,
                'nomor_telepon' => null,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ];
            $total++;

            // Sisipkan per 500 baris agar hemat memori.
            if (count($baris) >= 500) {
                DB::table('mahasiswa')->insertOrIgnore($baris);
                $baris = [];
            }
        }

        if ($baris !== []) {
            DB::table('mahasiswa')->insertOrIgnore($baris);
        }

        fclose($pegangan);

        $this->command?->info("Roster mahasiswa Teknik ter-seed: {$total} baris"
            .($dilewati > 0 ? " ({$dilewati} dilewati — kode prodi tak dikenal)." : '.'));
    }
}
