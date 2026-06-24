<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel kegiatan promosi/PMB — modul pendukung CRUD (non-fokus, BUKAN
     * Random Forest, BUKAN fokus penelitian). Versi ringkas single-entity:
     * mencatat kegiatan promosi ke sekolah target beserta hasilnya. Dapat
     * diperluas ke master petugas/sekolah/jadwal di kemudian hari.
     */
    public function up(): void
    {
        Schema::create('kegiatan_promosi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama_kegiatan');
            $tabel->string('sekolah_target');
            $tabel->string('kota')->nullable();
            $tabel->date('tanggal')->nullable();
            $tabel->string('petugas')->nullable()->comment('Penanggung jawab/petugas promosi');
            $tabel->unsignedInteger('jumlah_peminat')->nullable()->comment('Hasil: perkiraan calon peminat');
            $tabel->text('catatan')->nullable();
            $tabel->timestamps();

            $tabel->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kegiatan_promosi');
    }
};
