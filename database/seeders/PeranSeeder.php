<?php

namespace Database\Seeders;

use App\Models\IzinPeran;
use App\Models\Peran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

/**
 * Mengisi tabel peran + penugasan izin. Sumber awal = peta di config/peran.php
 * (sehingga perilaku identik dengan sebelum migrasi ke DB), DITAMBAH peran baru
 * `wakil_rektor_3` (akses Laporan) sesuai masukan dosen pembimbing.
 *
 * Idempoten: firstOrCreate peran + sinkronisasi penugasan izin.
 */
class PeranSeeder extends Seeder
{
    public function run(): void
    {
        // Metadata peran (nama & deskripsi ramah-pengguna).
        $meta = [
            'admin'         => ['Administrator', 'Manajemen sistem, pengguna & hak akses, konfigurasi.', true, true],
            'wd3'           => ['Wakil Dekan III', 'Konsumen utama dashboard hasil klasterisasi & laporan.', false, false],
            'staf_wd3'      => ['Staf WD III', 'Kelola data mahasiswa & IPK; menjalankan klasterisasi.', false, false],
            'kaprodi'       => ['Ketua Program Studi', 'Monitoring IPK mahasiswa (read-only).', false, false],
            'staf_prodi'    => ['Staf Prodi', 'Perbarui prestasi akademik & non-akademik.', false, false],
            'wakil_rektor_3'=> ['Wakil Rektor III', 'Akses laporan tingkat universitas.', false, false],
        ];

        // Peta izin dari config (untuk 5 peran lama).
        $petaConfig = (array) Config::get('peran.peta', []);

        // Peran baru: Wakil Rektor III → hanya Laporan.
        $petaConfig['wakil_rektor_3'] = ['laporan.lihat', 'laporan.ekspor'];

        foreach ($meta as $kode => [$nama, $deskripsi, $dilindungi, $wildcard]) {
            $peran = Peran::firstOrCreate(
                ['kode' => $kode],
                ['nama' => $nama, 'deskripsi' => $deskripsi, 'dilindungi' => $dilindungi, 'wildcard' => $wildcard],
            );

            // Peran wildcard (admin) tidak perlu baris izin eksplisit.
            if ($peran->wildcard) {
                continue;
            }

            $kodeIzin = array_values(array_unique((array) ($petaConfig[$kode] ?? [])));
            foreach ($kodeIzin as $izin) {
                IzinPeran::firstOrCreate(['peran_id' => $peran->id, 'izin_kode' => $izin]);
            }
        }

        Peran::lupakanCache();
    }
}
