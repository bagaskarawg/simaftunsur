<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dua tabel penyimpanan hasil klasterisasi K-Means:
     *
     *  - `klasterisasi_eksekusi` : header satu kali eksekusi (run) — k terpilih,
     *    metrik evaluasi, tabel evaluasi per-k, profil klaster, peringatan.
     *  - `klasterisasi_anggota`  : label klaster + koordinat PCA per mahasiswa
     *    untuk eksekusi tersebut.
     *
     * Hasil disimpan agar dashboard tidak perlu memanggil ulang service Python
     * setiap halaman dibuka, dan agar riwayat eksekusi dapat ditelusuri.
     */
    public function up(): void
    {
        Schema::create('klasterisasi_eksekusi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedTinyInteger('k_terpilih');
            $tabel->string('metode_pemilihan_k')->comment('otomatis (Silhouette) / manual');
            $tabel->json('fitur_dipakai');
            $tabel->string('skema_penskalaan', 16)->default('standard');
            $tabel->unsignedInteger('jumlah_data');
            $tabel->decimal('silhouette', 6, 4)->nullable()->comment('[-1,1], makin tinggi makin baik');
            $tabel->decimal('davies_bouldin', 6, 4)->nullable()->comment('>=0, makin rendah makin baik');
            $tabel->double('inertia')->nullable()->comment('WCSS untuk Elbow');
            $tabel->json('evaluasi_k')->comment('metrik per-k untuk grafik Elbow & Silhouette');
            $tabel->json('profil_klaster')->comment('centroid + label + jumlah anggota tiap klaster');
            $tabel->json('peringatan')->nullable()->comment('catatan kejujuran, mis. data < 100');
            $tabel->foreignId('dijalankan_oleh')
                ->nullable()
                ->constrained('pengguna')
                ->nullOnDelete();
            $tabel->timestamps();
        });

        Schema::create('klasterisasi_anggota', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('eksekusi_id')
                ->constrained('klasterisasi_eksekusi')
                ->cascadeOnDelete();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnDelete();
            $tabel->unsignedTinyInteger('cluster')->comment('label klaster, 0-indexed dari scikit-learn');
            $tabel->double('pca_x')->default(0);
            $tabel->double('pca_y')->default(0);

            $tabel->unique(['eksekusi_id', 'mahasiswa_id']);
            $tabel->index(['eksekusi_id', 'cluster']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klasterisasi_anggota');
        Schema::dropIfExists('klasterisasi_eksekusi');
    }
};
