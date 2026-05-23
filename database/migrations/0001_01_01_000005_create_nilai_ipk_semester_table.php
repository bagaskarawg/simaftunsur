<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel rekam jejak IPK per semester. Menjadi sumber fitur utama
     * untuk klasterisasi K-Means (rata-rata, tren, konsistensi).
     */
    public function up(): void
    {
        Schema::create('nilai_ipk_semester', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->unsignedTinyInteger('semester')->comment('Rentang 1-14');
            $tabel->string('tahun_akademik', 9)->comment('Format 2025/2026');
            $tabel->enum('semester_ganjil_genap', ['ganjil', 'genap']);
            $tabel->decimal('ipk', 3, 2);
            $tabel->unsignedSmallInteger('sks_diambil')->default(0);
            $tabel->unsignedSmallInteger('sks_lulus')->default(0);
            $tabel->timestamps();

            $tabel->unique(['mahasiswa_id', 'semester']);
            $tabel->index('tahun_akademik');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai_ipk_semester');
    }
};
