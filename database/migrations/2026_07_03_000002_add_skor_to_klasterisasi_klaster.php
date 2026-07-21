<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Simpan skor komposit multi-fitur (dasar penentuan label) pada tiap klaster,
 * agar dashboard & halaman detail dapat menampilkan ALASAN penamaan secara
 * transparan (bukan sekadar nama). Diisi dari keluaran service Python.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('klasterisasi_klaster', function (Blueprint $tabel) {
            $tabel->double('skor_akademik')->nullable()->after('centroid_terskala');
            $tabel->double('skor_non_akademik')->nullable()->after('skor_akademik');
            $tabel->double('skor_komposit')->nullable()->after('skor_non_akademik');
            $tabel->text('ringkasan_profil')->nullable()->after('skor_komposit');
        });
    }

    public function down(): void
    {
        Schema::table('klasterisasi_klaster', function (Blueprint $tabel) {
            $tabel->dropColumn(['skor_akademik', 'skor_non_akademik', 'skor_komposit', 'ringkasan_profil']);
        });
    }
};
