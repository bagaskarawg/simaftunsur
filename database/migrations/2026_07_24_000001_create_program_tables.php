<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel modul PENYARINGAN KANDIDAT berbasis persyaratan program.
     *
     *  - `program`        : definisi program (beasiswa/prestasi mahasiswa/lainnya).
     *  - `program_syarat` : kriteria terstruktur per program. Satu baris = satu
     *    kriteria boolean (bidang + operator + nilai + wajib). SENGAJA TIDAK ada
     *    kolom bobot/skor: penyaringan bersifat lolos/tidak per kriteria, tanpa
     *    agregasi (menghormati batasan metodologi — bukan SAW/Profile Matching).
     */
    public function up(): void
    {
        Schema::create('program', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama');
            $tabel->enum('jenis', ['beasiswa', 'prestasi_mahasiswa', 'lainnya'])
                ->default('beasiswa')
                ->comment('Jenis program kemahasiswaan');
            $tabel->text('deskripsi')->nullable();
            $tabel->string('penyelenggara')->nullable();
            $tabel->date('pendaftaran_mulai')->nullable();
            $tabel->date('pendaftaran_selesai')->nullable();
            $tabel->unsignedInteger('kuota')->nullable()
                ->comment('Kuota informatif — TIDAK memotong daftar kandidat otomatis');
            $tabel->boolean('aktif')->default(true);
            $tabel->foreignId('dibuat_oleh')->nullable()
                ->constrained('pengguna')->nullOnDelete();
            $tabel->timestamps();
            $tabel->softDeletes();
        });

        Schema::create('program_syarat', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('program_id')->constrained('program')->cascadeOnDelete();
            $tabel->string('bidang')->comment('Nilai enum BidangKriteria, mis. ipk_rata_rata');
            $tabel->string('operator')->comment('Nilai enum OperatorKriteria: gte/lte/gt/lt/eq/in');
            $tabel->string('nilai')->comment('Ambang; JSON untuk operator in / field khusus');
            $tabel->boolean('wajib')->default(true)
                ->comment('Wajib ikut AND kelayakan; tidak wajib hanya informatif');
            $tabel->string('label')->comment('Kalimat syarat Bahasa Indonesia untuk tampilan');
            $tabel->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_syarat');
        Schema::dropIfExists('program');
    }
};
