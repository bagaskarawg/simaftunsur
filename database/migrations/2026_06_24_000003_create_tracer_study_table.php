<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel tracer study alumni — modul pendukung CRUD (non-fokus, tanpa
     * sistem cerdas). Merekam kondisi alumni setelah lulus untuk SIMKATMAWA.
     */
    public function up(): void
    {
        Schema::create('tracer_study', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->year('tahun_lulus')->nullable();
            $tabel->enum('status_pekerjaan', ['bekerja', 'wirausaha', 'lanjut_studi', 'belum_bekerja'])
                ->default('belum_bekerja');
            $tabel->unsignedSmallInteger('masa_tunggu_bulan')->nullable()
                ->comment('Bulan dari lulus hingga pekerjaan pertama');
            $tabel->string('nama_instansi')->nullable();
            $tabel->enum('relevansi', ['sangat_relevan', 'relevan', 'kurang_relevan', 'tidak_relevan'])
                ->nullable()->comment('Relevansi pekerjaan dengan bidang studi');
            $tabel->string('rentang_gaji')->nullable()->comment("mis. '< 3 juta', '3-5 juta'");
            $tabel->date('tanggal_isi')->nullable();
            $tabel->timestamps();

            $tabel->index('status_pekerjaan');
            $tabel->index('tahun_lulus');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_study');
    }
};
