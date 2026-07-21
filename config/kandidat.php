<?php

/*
|--------------------------------------------------------------------------
| Preset Program Kandidat (SIMKATMAWA)
|--------------------------------------------------------------------------
|
| Tindak lanjut hasil klaster: menampilkan LANGSUNG identitas mahasiswa yang
| cocok untuk sebuah program (mawapres, beasiswa, dll), DIURUTKAN pada SATU
| ukuran objektif yang sudah ada di sistem (IPK / poin SKKM). Ini sengaja
| "pengurutan biasa" (single orderBy), BUKAN pembobotan MCDM (SAW/WP/AHP/
| TOPSIS) yang dilarang — tidak ada normalisasi + bobot preferensi.
|
| Tiap preset:
|   - syarat.ipk_min       : ambang IPK rata-rata minimum penyaring.
|   - syarat.butuh_prestasi: bila true, hanya mahasiswa dengan skor prestasi > 0.
|   - urut.kolom / urut.arah: kolom pengurutan bawaan (boleh diubah pengguna
|     dari daftar kolom_urut di bawah) + arah (desc/asc).
|   - catatan              : disiplin kejujuran — persyaratan yang TIDAK ada di
|     sistem (mis. berkas ekonomi KIP) harus diverifikasi terpisah.
|
| Angka ambang adalah NILAI AWAL yang dapat ditinjau bersama WD III.
|
*/

return [

    // Batas jumlah baris yang ditampilkan (kandidat teratas). Sisanya diringkas.
    'batas_tampil' => 100,

    // Daftar putih kolom pengurutan (mencegah orderBy sembarang). Semua kolom
    // ini objektif & sudah dihitung sistem — bukan skor preferensi.
    'kolom_urut' => [
        'ipk_rata_rata' => 'IPK Rata-rata',
        'ipk_terakhir' => 'IPK Terakhir',
        'skor_prestasi' => 'Skor Prestasi (F5)',
        'skor_kegiatan' => 'Skor Kegiatan (F6)',
        'skor_pengabdian' => 'Skor Pengabdian (F7)',
        'skor_non_akademik' => 'Total Skor Non-Akademik',
    ],

    'program' => [

        'mawapres' => [
            'label' => 'Mahasiswa Berprestasi (Mawapres)',
            'deskripsi' => 'Kandidat dengan IPK baik dan rekam prestasi menonjol.',
            'syarat' => ['ipk_min' => 3.00, 'butuh_prestasi' => true],
            'urut' => ['kolom' => 'skor_prestasi', 'arah' => 'desc'],
            'catatan' => 'Seleksi Mawapres menimbang IPK, prestasi, dan karya tulis. '
                .'Urutan di sini berdasarkan skor prestasi objektif (SKKM) sebagai '
                .'penyaring awal; IPK dan karya tulis tetap ditinjau manual.',
        ],

        'beasiswa_prestasi' => [
            'label' => 'Beasiswa Prestasi Akademik (PPA)',
            'deskripsi' => 'Kandidat dengan IPK tinggi dan konsisten.',
            'syarat' => ['ipk_min' => 3.00, 'butuh_prestasi' => false],
            'urut' => ['kolom' => 'ipk_rata_rata', 'arah' => 'desc'],
            'catatan' => 'Diurutkan berdasarkan IPK rata-rata. Kelengkapan berkas '
                .'administratif diverifikasi terpisah.',
        ],

        'beasiswa_pemda' => [
            'label' => 'Beasiswa Pemerintah Daerah',
            'deskripsi' => 'Kandidat aktif dengan IPK memenuhi ambang.',
            'syarat' => ['ipk_min' => 3.00, 'butuh_prestasi' => false],
            'urut' => ['kolom' => 'ipk_rata_rata', 'arah' => 'desc'],
            'catatan' => 'Persyaratan domisili & ekonomi TIDAK tersedia di sistem — '
                .'daftar ini hanya menyaring IPK; verifikasi kelayakan lain dilakukan Pemda.',
        ],

        'kip_kuliah' => [
            'label' => 'KIP-Kuliah (pemeliharaan IPK)',
            'deskripsi' => 'Penerima yang wajib menjaga IPK minimum.',
            'syarat' => ['ipk_min' => 2.75, 'butuh_prestasi' => false],
            'urut' => ['kolom' => 'ipk_rata_rata', 'arah' => 'asc'],
            'catatan' => 'Data ekonomi/berkas KIP TIDAK tersedia di sistem. Diurutkan '
                .'IPK menaik agar penerima ber-IPK rendah (berisiko) tampil lebih dulu.',
        ],

    ],

];
