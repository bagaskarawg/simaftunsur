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
| Disimpan sebagai config agar besaran mudah difinalisasi bersama Wakil Dekan
| III tanpa mengubah kode. Metode skor pada model membaca tabel ini.
|
*/

return [

    /*
     | F5 — Prestasi/Kejuaraan.  Poin = [tingkat][capaian].
     | Pemetaan tingkat enum Prestasi → jenjang rubrik:
     |   internasional → Internasional
     |   nasional      → Nasional
     |   regional      → Provinsi/Regional
     |   lokal         → Universitas/Fakultas
     */
    'prestasi' => [
        'internasional' => ['juara_1' => 100, 'juara_2' => 90, 'juara_3' => 80, 'finalis' => 40],
        'nasional'      => ['juara_1' => 80,  'juara_2' => 70, 'juara_3' => 60, 'finalis' => 30],
        'regional'      => ['juara_1' => 60,  'juara_2' => 50, 'juara_3' => 40, 'finalis' => 20],
        'lokal'         => ['juara_1' => 40,  'juara_2' => 30, 'juara_3' => 20, 'finalis' => 10],
    ],

    /*
     | F6 — Kegiatan & Organisasi.  Poin = [jenis][peran].
     */
    'kegiatan' => [
        'organisasi'  => ['ketua' => 40, 'wakil' => 30, 'pengurus_inti' => 25, 'anggota' => 10],
        'kepanitiaan' => ['ketua' => 20, 'koordinator' => 15, 'anggota' => 8],
        'seminar'     => ['pembicara' => 20, 'peserta' => 5],
    ],

    /*
     | F7 — Pengabdian & Hibah.  Poin = [jenis][peran].
     */
    'pengabdian' => [
        'hibah_didanai'         => ['ketua' => 50, 'anggota' => 30],
        'proposal_lolos'        => ['ketua' => 20, 'anggota' => 12],
        'pengabdian_masyarakat' => ['peserta_aktif' => 15],
    ],

];
