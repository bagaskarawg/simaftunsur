<?php

/*
|--------------------------------------------------------------------------
| Rubrik Poin SKKM (Sistem Kredit Kegiatan Mahasiswa)
|--------------------------------------------------------------------------
|
| Besaran poin OBJEKTIF berbasis bukti untuk tiga sub-skor non-akademik yang
| dipakai sebagai FITUR klasterisasi K-Means (F5, F6, F7), BUKAN pembobotan
| preferensi untuk memeringkat mahasiswa. Tiap bidang menjadi satu fitur
| tersendiri (lihat CLAUDE.md — larangan SAW/WP tetap dihormati karena tidak
| ada skor tunggal untuk ranking).
|
| Angka mengikuti rubrik naskah laporan (BAB II): Tabel 5 (prestasi/kejuaraan),
| Tabel 6 (kegiatan & organisasi), Tabel 7 (pengabdian & hibah).
|
| PLAFON AKUMULASI: poin diakumulasi maksimal `plafon_per_grup` item ber-poin
| TERTINGGI per GRUP per TAHUN KALENDER. Grup = tingkat (prestasi) / jenis
| (kegiatan, pengabdian). Perhitungan plafon ada di Model Mahasiswa
| (skorPrestasi/skorKegiatan/skorPengabdian).
|
| (*) Komponen adaptasi yang tidak tercakup rubrik CIC asli; besaran diusulkan
| konsisten dengan skala CIC (praktik penilaian merujuk pedoman SKKM UNISM 2019
| & IAINU Tuban 2020), difinalisasi bersama Wakil Dekan III. Perlakuan plafonnya
| sama seperti komponen lain.
|
| Disimpan sebagai config agar besaran mudah ditinjau bersama WD III tanpa
| mengubah kode. Metode skor pada model membaca tabel ini.
|
*/

return [

    // Batas maksimal item ber-poin tertinggi yang diakumulasi per grup per tahun.
    'plafon_per_grup' => 3,

    /*
     | F5 — Prestasi/Kejuaraan (Tabel 5). Poin = [tingkat][capaian].
     | Pemetaan tingkat enum Prestasi → jenjang rubrik:
     |   internasional → Internasional
     |   nasional      → Nasional
     |   regional      → Provinsi/Regional
     |   lokal         → Universitas/Fakultas
     */
    'prestasi' => [
        'internasional' => ['juara_1' => 25, 'juara_2' => 22, 'juara_3' => 18, 'finalis' => 10],
        'nasional' => ['juara_1' => 20, 'juara_2' => 17, 'juara_3' => 14, 'finalis' => 8],
        'regional' => ['juara_1' => 10, 'juara_2' => 8,  'juara_3' => 5,  'finalis' => 2],
        'lokal' => ['juara_1' => 8,  'juara_2' => 5,  'juara_3' => 3,  'finalis' => 1],
    ],

    /*
     | F6 — Kegiatan & Organisasi (Tabel 6). Poin = [jenis][peran].
     */
    'kegiatan' => [
        // Pengurus organisasi kemahasiswaan (BEM/DPM/HMJ), per periode.
        'organisasi' => ['ketua_umum' => 20, 'wakil_ketua' => 18, 'pengurus_inti' => 17, 'anggota_pengurus' => 10],
        // Pengurus UKM, per periode.
        'ukm' => ['ketua_ukm' => 10],
        // Kepanitiaan kegiatan (tingkat universitas/fakultas).
        'kepanitiaan' => ['ketua' => 10, 'wakil_ketua' => 9, 'sekretaris_bendahara' => 8, 'koordinator_seksi' => 7, 'anggota' => 5],
        // Seminar/Workshop/Pelatihan (*).
        'seminar' => ['pembicara' => 8, 'peserta' => 1],
    ],

    /*
     | F7 — Pengabdian & Hibah (Tabel 7). Poin = [jenis][peran].
     */
    'pengabdian' => [
        'pimnas' => ['ketua' => 40, 'anggota' => 35],
        'hibah_didanai' => ['ketua' => 35, 'anggota' => 30],
        'proposal_lolos' => ['ketua' => 15, 'anggota' => 10], // (*)
        'pengabdian_masyarakat' => ['dalam_kampus' => 1, 'luar_kampus' => 3],
    ],

];
