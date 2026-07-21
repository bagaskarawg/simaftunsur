<?php

/*
|--------------------------------------------------------------------------
| Konfigurasi Pelabelan Klaster (Skor Komposit Multi-Fitur)
|--------------------------------------------------------------------------
|
| Bobot & arah tiap fitur untuk menghitung SKOR KOMPOSIT yang menjadi dasar
| peringkat & penamaan klaster. Nilai ini dikirim ke service Python bersama
| katalog nama (dari master KlasterisasiKategori) pada tiap eksekusi.
|
| - arah: +1 = makin tinggi makin baik; -1 = makin tinggi makin buruk.
|         (konsistensi = standar deviasi IPK → makin KECIL makin stabil → -1)
| - bobot: kontribusi relatif fitur DI DALAM dimensinya (dinormalisasi).
| - bobot dimensi: porsi blok akademik vs non-akademik pada skor komposit.
|
| Angka default (40/30/20/10 akademik; 50/30/20 non-akademik; 50:50 dimensi)
| adalah NILAI AWAL yang dapat ditinjau bersama pembimbing/WD III — bukan
| angka baku. Ubah di sini bila kebijakan penilaian berubah.
|
| Penjelasan lengkap + rumus + contoh: docs/pelabelan-klaster.md
|
*/

return [

    'label' => [

        'dimensi' => [
            'akademik' => [
                'bobot' => 0.5,
                'fitur' => [
                    'ipk_rata_rata' => ['bobot' => 0.40, 'arah' => 1],
                    'ipk_terakhir'  => ['bobot' => 0.30, 'arah' => 1],
                    'tren'          => ['bobot' => 0.20, 'arah' => 1],
                    'konsistensi'   => ['bobot' => 0.10, 'arah' => -1],
                ],
            ],
            'non_akademik' => [
                'bobot' => 0.5,
                'fitur' => [
                    'skor_prestasi'   => ['bobot' => 0.50, 'arah' => 1],
                    'skor_kegiatan'   => ['bobot' => 0.30, 'arah' => 1],
                    'skor_pengabdian' => ['bobot' => 0.20, 'arah' => 1],
                ],
            ],
        ],

        // Ambang z-score untuk deskripsi kualitatif sub-dimensi (tinggi/sedang/rendah).
        'ambang_tinggi' => 0.25,
        'ambang_rendah' => -0.25,

    ],

];
