<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel modul KKN (Kuliah Kerja Nyata) — modul pendukung CRUD (non-fokus,
     * tanpa sistem cerdas). Alur: Lokasi → Kelompok (ditempatkan di satu lokasi
     * & dibimbing satu DPL) → Peserta (mahasiswa anggota kelompok) → Nilai akhir.
     *
     * Penilaian/monitoring digabung ke tabel peserta; data mitra disimpan
     * sebagai kolom pada lokasi agar jumlah tabel tetap ringkas.
     */
    public function up(): void
    {
        Schema::create('kkn_lokasi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama')->comment('Desa/kelurahan lokasi KKN');
            $tabel->string('kecamatan')->nullable();
            $tabel->string('kabupaten')->nullable()->default('Cianjur');
            $tabel->string('tahun_akademik', 9)->comment("mis. '2025/2026'");
            $tabel->unsignedSmallInteger('kuota')->nullable();
            $tabel->string('mitra')->nullable()->comment('Instansi/mitra kerja sama');
            $tabel->boolean('aktif')->default(true);
            $tabel->text('keterangan')->nullable();
            $tabel->timestamps();

            $tabel->index('tahun_akademik');
            $tabel->index('aktif');
        });

        Schema::create('kkn_dpl', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama');
            $tabel->string('nip')->nullable();
            $tabel->string('nomor_telepon')->nullable();
            $tabel->string('bidang_keahlian')->nullable();
            $tabel->boolean('aktif')->default(true);
            $tabel->timestamps();
        });

        Schema::create('kkn_kelompok', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama_kelompok');
            $tabel->foreignId('kkn_lokasi_id')
                ->constrained('kkn_lokasi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $tabel->foreignId('kkn_dpl_id')
                ->nullable()
                ->constrained('kkn_dpl')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $tabel->string('tahun_akademik', 9);
            $tabel->enum('status', ['persiapan', 'berjalan', 'selesai'])->default('persiapan');
            $tabel->text('keterangan')->nullable();
            $tabel->timestamps();

            $tabel->index('status');
            $tabel->index('tahun_akademik');
        });

        Schema::create('kkn_peserta', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('kkn_kelompok_id')
                ->constrained('kkn_kelompok')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->enum('jabatan', ['ketua', 'sekretaris', 'bendahara', 'anggota'])->default('anggota');
            $tabel->enum('status', ['terdaftar', 'aktif', 'selesai', 'mengundurkan_diri'])->default('terdaftar');
            $tabel->decimal('nilai_akhir', 5, 2)->nullable()->comment('Skala 0-100');
            $tabel->string('nilai_huruf', 2)->nullable()->comment('mis. A, B+, C');
            $tabel->text('catatan')->nullable();
            $tabel->timestamps();

            // Satu mahasiswa hanya sekali dalam satu kelompok.
            $tabel->unique(['kkn_kelompok_id', 'mahasiswa_id']);
            $tabel->index('mahasiswa_id');
            $tabel->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kkn_peserta');
        Schema::dropIfExists('kkn_kelompok');
        Schema::dropIfExists('kkn_dpl');
        Schema::dropIfExists('kkn_lokasi');
    }
};
