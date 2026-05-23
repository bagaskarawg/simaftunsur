<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel mahasiswa beserta kolom label `status_akhir`
     * yang disiapkan untuk pengembangan lanjutan Random Forest
     * (lihat CLAUDE.md poin 3 — "Database harus siap menampung label").
     */
    public function up(): void
    {
        Schema::create('mahasiswa', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nim', 11)->unique();
            $tabel->string('nama');
            $tabel->foreignId('program_studi_id')
                ->constrained('program_studi')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $tabel->year('angkatan');
            $tabel->unsignedTinyInteger('semester_aktif')->default(1)
                ->comment('Rentang 1-14');
            $tabel->enum('jenis_kelamin', ['L', 'P']);
            $tabel->enum('status', ['aktif', 'cuti', 'non_aktif', 'lulus', 'do'])
                ->default('aktif');
            $tabel->enum('status_akhir', ['lulus_tepat', 'lulus_terlambat', 'do'])
                ->nullable()
                ->comment('Kolom label untuk migrasi Random Forest masa depan; belum dipakai sekarang');
            $tabel->string('email')->nullable();
            $tabel->string('nomor_telepon', 20)->nullable();
            $tabel->timestamps();

            $tabel->index(['program_studi_id', 'angkatan']);
            $tabel->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mahasiswa');
    }
};
