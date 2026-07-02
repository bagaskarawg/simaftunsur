<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kegiatan & Organisasi kemahasiswaan — sumber Skor Kegiatan (fitur F6).
     * Modul pendukung CRUD; poin dihitung dari rubrik SKKM (config/skkm.php)
     * berdasarkan pasangan (jenis, peran).
     */
    public function up(): void
    {
        Schema::create('kegiatan_kemahasiswaan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->enum('jenis', ['organisasi', 'kepanitiaan', 'seminar']);
            $tabel->string('peran')->comment('Divalidasi sesuai jenis (mis. ketua, koordinator, peserta)');
            $tabel->string('nama_kegiatan');
            $tabel->string('penyelenggara')->nullable();
            $tabel->string('periode')->nullable()->comment("mis. '2025/2026' untuk organisasi");
            $tabel->date('tanggal')->nullable();
            $tabel->string('url_bukti')->nullable();
            $tabel->text('keterangan')->nullable();
            $tabel->timestamps();

            $tabel->index('mahasiswa_id');
            $tabel->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_kemahasiswaan');
    }
};
