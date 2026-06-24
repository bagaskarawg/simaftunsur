<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel prestasi mahasiswa (akademik/non-akademik) — modul pendukung CRUD,
     * BUKAN fokus penelitian & tanpa sistem cerdas.
     */
    public function up(): void
    {
        Schema::create('prestasi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->string('judul');
            $tabel->enum('jenis', ['akademik', 'non_akademik'])->default('akademik');
            $tabel->enum('tingkat', ['lokal', 'regional', 'nasional', 'internasional'])->default('lokal');
            $tabel->string('peringkat')->nullable()->comment("mis. 'Juara 1', 'Finalis'");
            $tabel->string('penyelenggara')->nullable();
            $tabel->date('tanggal')->nullable();
            $tabel->string('url_bukti')->nullable();
            $tabel->timestamps();

            $tabel->index(['jenis', 'tingkat']);
            $tabel->index('mahasiswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prestasi');
    }
};
