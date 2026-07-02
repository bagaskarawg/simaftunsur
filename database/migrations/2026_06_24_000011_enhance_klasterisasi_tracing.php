<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Peningkatan keterlacakan (traceability) hasil K-Means agar setiap
     * penempatan mahasiswa ke sebuah klaster dapat dipertanggungjawabkan:
     *
     *  1. Tabel baru `klasterisasi_klaster` — menormalkan profil tiap klaster
     *     (centroid + label + interpretasi) dari JSON `profil_klaster` agar
     *     dapat direlasikan & di-query. Inilah DASAR penamaan klaster.
     *  2. Kolom snapshot pada `klasterisasi_anggota` — membekukan nilai fitur
     *     mahasiswa PADA SAAT run (satuan asli + terskala) beserta jarak ke
     *     centroid, sehingga justifikasi tetap valid meski data IPK berubah.
     *  3. Kolom reproduktibilitas pada `klasterisasi_eksekusi`.
     */
    public function up(): void
    {
        Schema::table('klasterisasi_eksekusi', function (Blueprint $tabel) {
            $tabel->integer('random_state')->nullable()->after('skema_penskalaan')
                ->comment('Seed KMeans untuk reproduktibilitas');
            $tabel->string('versi_algoritma')->nullable()->after('random_state')
                ->comment('mis. scikit-learn 1.5.0 / versi service');
            $tabel->string('kriteria_data')->nullable()->after('versi_algoritma')
                ->comment('Deskripsi kohort, mis. "mahasiswa aktif >=3 semester"');
        });

        Schema::create('klasterisasi_klaster', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('eksekusi_id')
                ->constrained('klasterisasi_eksekusi')
                ->cascadeOnDelete();
            $tabel->unsignedTinyInteger('cluster')->comment('label klaster, 0-indexed dari scikit-learn');
            $tabel->string('label_deskriptif')->nullable()->comment('mis. Berprestasi, Perlu Pembinaan');
            $tabel->unsignedInteger('jumlah_anggota')->default(0);
            $tabel->json('centroid')->comment('nilai centroid tiap fitur dalam SATUAN ASLI (dasar label)');
            $tabel->json('centroid_terskala')->nullable()->comment('centroid dalam ruang terskala yang dipakai KMeans');
            $tabel->text('interpretasi')->nullable()->comment('penjelasan karakteristik klaster');
            $tabel->timestamps();

            $tabel->unique(['eksekusi_id', 'cluster']);
        });

        Schema::table('klasterisasi_anggota', function (Blueprint $tabel) {
            $tabel->foreignId('klaster_id')->nullable()->after('eksekusi_id')
                ->constrained('klasterisasi_klaster')
                ->nullOnDelete();
            $tabel->json('fitur_nilai')->nullable()->after('cluster')
                ->comment('Snapshot fitur mahasiswa saat run (satuan asli) — dasar penempatan');
            $tabel->json('fitur_terskala')->nullable()->after('fitur_nilai')
                ->comment('Vektor fitur terskala yang diumpankan ke KMeans');
            $tabel->double('jarak_ke_centroid')->nullable()->after('fitur_terskala')
                ->comment('Jarak Euclid ke centroid klasternya (kekuatan keanggotaan)');
        });
    }

    public function down(): void
    {
        Schema::table('klasterisasi_anggota', function (Blueprint $tabel) {
            $tabel->dropConstrainedForeignId('klaster_id');
            $tabel->dropColumn(['fitur_nilai', 'fitur_terskala', 'jarak_ke_centroid']);
        });

        Schema::dropIfExists('klasterisasi_klaster');

        Schema::table('klasterisasi_eksekusi', function (Blueprint $tabel) {
            $tabel->dropColumn(['random_state', 'versi_algoritma', 'kriteria_data']);
        });
    }
};
