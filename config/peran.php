<?php

/*
|--------------------------------------------------------------------------
| Peta Peran → Izin (RBAC Sederhana)
|--------------------------------------------------------------------------
|
| Daftar peran dan izin yang dimiliki tiap peran. Sengaja disimpan
| sebagai konfigurasi statis PHP (bukan tabel) agar:
|   - Tidak ada ketergantungan paket pihak ketiga (mis. spatie/permission).
|   - Mudah di-review lewat git diff saat ada perubahan kebijakan akses.
|   - Cocok dengan stack Laravel 13 + Livewire 4 yang dipakai SIMAFTUNSUR.
|
| Konvensi kode izin: "{modul}.{aksi}" dalam Bahasa Indonesia
| (mis. mahasiswa.lihat, mahasiswa.kelola, klasterisasi.jalankan).
|
| Peran 'admin' memakai wildcard '*' yang berarti seluruh izin.
| Penanganan wildcard ada di App\Models\Pengguna::punyaIzin().
|
| Lima peran (kesepakatan bimbingan 2 Juli 2026):
|   admin      — Administrator: manajemen sistem, pengguna & hak akses,
|                konfigurasi & pemeliharaan.
|   wd3        — Wakil Dekan III: konsumen utama — dashboard hasil
|                klasterisasi, profil/segmen mahasiswa, laporan.
|   staf_wd3   — Staf WD III: kelola data mahasiswa & IPK per semester;
|                menjalankan proses klasterisasi.
|   kaprodi    — Ketua Program Studi: monitoring IPK mahasiswa dan IPK
|                penerima beasiswa (read-only).
|   staf_prodi — Staf Prodi: update prestasi akademik & non-akademik;
|                kelola data kegiatan kemahasiswaan.
|
| CATATAN: izin kelola untuk modul tracer, promosi, beasiswa, kkn, dan
| pengabdian saat ini hanya dimiliki admin (wildcard) karena belum ada
| peran fungsional yang ditetapkan untuk mengelolanya.
|
*/

return [

    /*
    | Daftar kanonik seluruh kode izin yang dikenal sistem. Gate Laravel
    | didaftarkan dari daftar ini (bukan dari peta peran), sehingga izin
    | yang saat ini hanya dimiliki admin lewat wildcard — mis. tracer.kelola
    | — tetap berfungsi meski tidak tercantum pada peran mana pun.
    */
    'izin' => [
        'mahasiswa.lihat',
        'mahasiswa.kelola',
        'klasterisasi.lihat',
        'klasterisasi.jalankan',
        'program.lihat',
        'program.kelola',
        'program.saring',
        'program.ekspor',
        'kategori-klaster.lihat',
        'kategori-klaster.kelola',
        'laporan.lihat',
        'laporan.ekspor',
        'prestasi.lihat',
        'prestasi.kelola',
        'kegiatan.lihat',
        'kegiatan.kelola',
        'pengabdian.lihat',
        'pengabdian.kelola',
        'beasiswa.lihat',
        'beasiswa.kelola',
        'kkn.lihat',
        'kkn.kelola',
        'tracer.lihat',
        'tracer.kelola',
        'promosi.lihat',
        'promosi.kelola',
    ],

    'peta' => [

        'admin' => ['*'],

        'wd3' => [
            'mahasiswa.lihat',
            'klasterisasi.lihat',
            'program.lihat',
            'program.kelola',
            'program.saring',
            'program.ekspor',
            'kategori-klaster.lihat',
            'kategori-klaster.kelola',
            'laporan.lihat',
            'laporan.ekspor',
            'prestasi.lihat',
            'kegiatan.lihat',
            'pengabdian.lihat',
            'beasiswa.lihat',
            'kkn.lihat',
            'tracer.lihat',
            'promosi.lihat',
        ],

        'staf_wd3' => [
            'mahasiswa.lihat',
            'mahasiswa.kelola',
            'klasterisasi.lihat',
            'klasterisasi.jalankan',
            'program.lihat',
            'program.kelola',
            'program.saring',
            'program.ekspor',
        ],

        'kaprodi' => [
            'mahasiswa.lihat',
            'beasiswa.lihat',
            'program.lihat',
        ],

        'staf_prodi' => [
            'mahasiswa.lihat',
            'prestasi.lihat',
            'prestasi.kelola',
            'kegiatan.lihat',
            'kegiatan.kelola',
        ],

    ],

];
