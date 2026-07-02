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
*/

return [

    'peta' => [

        'admin' => ['*'],

        'dekan' => [
            'mahasiswa.lihat',
            'klasterisasi.lihat',
            'laporan.lihat',
            'prestasi.lihat',
            'tracer.lihat',
            'promosi.lihat',
            'beasiswa.lihat',
            'kkn.lihat',
            'kegiatan.lihat',
            'pengabdian.lihat',
        ],

        'wd3' => [
            'mahasiswa.lihat',
            'mahasiswa.kelola',
            'klasterisasi.lihat',
            'klasterisasi.jalankan',
            'laporan.lihat',
            'laporan.ekspor',
            'prestasi.lihat',
            'prestasi.kelola',
            'tracer.lihat',
            'tracer.kelola',
            'promosi.lihat',
            'promosi.kelola',
            'beasiswa.lihat',
            'beasiswa.kelola',
            'kkn.lihat',
            'kkn.kelola',
            'kegiatan.lihat',
            'kegiatan.kelola',
            'pengabdian.lihat',
            'pengabdian.kelola',
        ],

        'kaprodi' => [
            'mahasiswa.lihat',
            'laporan.lihat',
            'prestasi.lihat',
            'tracer.lihat',
            'promosi.lihat',
            'beasiswa.lihat',
            'kkn.lihat',
            'kegiatan.lihat',
            'pengabdian.lihat',
        ],

        'staf' => [
            'mahasiswa.lihat',
            'mahasiswa.kelola',
            'prestasi.lihat',
            'prestasi.kelola',
            'tracer.lihat',
            'tracer.kelola',
            'promosi.lihat',
            'promosi.kelola',
            'beasiswa.lihat',
            'beasiswa.kelola',
            'kkn.lihat',
            'kkn.kelola',
            'kegiatan.lihat',
            'kegiatan.kelola',
            'pengabdian.lihat',
            'pengabdian.kelola',
        ],

        'dosen' => [
            'mahasiswa.lihat',
            'prestasi.lihat',
            'kkn.lihat',
            'kegiatan.lihat',
            'pengabdian.lihat',
        ],

    ],

];
