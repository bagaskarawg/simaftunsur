<?php

namespace Database\Seeders;

use App\Models\Program;
use Illuminate\Database\Seeder;

/**
 * Program contoh untuk demo Penyaringan Kandidat. SIMULASI — persyaratan
 * disusun agar menunjukkan berbagai jenis kriteria (akademik, non-akademik,
 * jumlah prestasi per tingkat, label klaster opsional). Data non-akademik
 * mahasiswa sengaja timpang (mayoritas 0) sesuai kondisi nyata; analisis
 * klasterisasi di laporan tetap memakai data riil F1–F4.
 */
class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        // Idempoten (sekali isi): lewati bila program contoh sudah pernah dibuat,
        // agar aman dijalankan tiap deploy (db:seed berulang).
        if (Program::query()->exists()) {
            return;
        }

        // 1) Beasiswa: butuh status aktif + IPK memadai + keterlibatan kegiatan.
        $beasiswa = Program::create([
            'nama' => 'Beasiswa Unggulan 2026',
            'jenis' => 'beasiswa',
            'deskripsi' => 'Beasiswa bagi mahasiswa aktif berprestasi akademik dan aktif berorganisasi.',
            'penyelenggara' => 'Fakultas Teknik UNSUR',
            'kuota' => 20,
            'aktif' => true,
        ]);
        $beasiswa->syarat()->createMany([
            ['bidang' => 'status', 'operator' => 'eq', 'nilai' => 'aktif', 'wajib' => true, 'label' => 'Status mahasiswa = Aktif'],
            ['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '3.25', 'wajib' => true, 'label' => 'IPK rata-rata ≥ 3,25'],
            ['bidang' => 'skor_kegiatan', 'operator' => 'gte', 'nilai' => '20', 'wajib' => true, 'label' => 'Skor kegiatan/organisasi (F6) ≥ 20'],
        ]);

        // 2) Pilmapres: IPK tinggi + prestasi nasional; klaster sebagai info opsional.
        $pilmapres = Program::create([
            'nama' => 'Pemilihan Mahasiswa Berprestasi (Pilmapres) FT 2026',
            'jenis' => 'prestasi_mahasiswa',
            'deskripsi' => 'Seleksi awal kandidat Pilmapres tingkat fakultas.',
            'penyelenggara' => 'Wakil Dekan III FT UNSUR',
            'kuota' => 5,
            'aktif' => true,
        ]);
        $pilmapres->syarat()->createMany([
            ['bidang' => 'ipk_terakhir', 'operator' => 'gte', 'nilai' => '3.50', 'wajib' => true, 'label' => 'IPK terakhir ≥ 3,50'],
            [
                'bidang' => 'jumlah_prestasi_min_tingkat',
                'operator' => 'gte',
                'nilai' => json_encode(['tingkat' => 'nasional', 'min_jumlah' => 1]),
                'wajib' => true,
                'label' => 'Minimal 1 prestasi tingkat Nasional',
            ],
            // Kriteria opsional (tidak memengaruhi kelayakan) — hanya informatif.
            ['bidang' => 'label_klaster', 'operator' => 'eq', 'nilai' => 'Berprestasi', 'wajib' => false, 'label' => 'Label klaster (K-Means) = Berprestasi'],
        ]);

        // 3) Beasiswa berbasis ekonomi: IPK cukup + ekonomi bawah/menengah.
        $beasiswaEkonomi = Program::create([
            'nama' => 'Beasiswa Bantuan Ekonomi 2026',
            'jenis' => 'beasiswa',
            'deskripsi' => 'Bantuan bagi mahasiswa aktif berekonomi rendah/menengah dengan IPK memadai.',
            'penyelenggara' => 'Fakultas Teknik UNSUR',
            'kuota' => 30,
            'aktif' => true,
        ]);
        $beasiswaEkonomi->syarat()->createMany([
            ['bidang' => 'status', 'operator' => 'eq', 'nilai' => 'aktif', 'wajib' => true, 'label' => 'Status mahasiswa = Aktif'],
            ['bidang' => 'ipk_rata_rata', 'operator' => 'gte', 'nilai' => '3.00', 'wajib' => true, 'label' => 'IPK rata-rata ≥ 3,00'],
            [
                'bidang' => 'kategori_ekonomi',
                'operator' => 'in',
                'nilai' => json_encode(['rendah', 'menengah']),
                'wajib' => true,
                'label' => 'Kategori ekonomi: Rendah atau Menengah',
            ],
        ]);
    }
}
