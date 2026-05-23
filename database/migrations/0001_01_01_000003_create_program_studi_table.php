<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel program studi (referensi prodi FT UNSUR).
     */
    public function up(): void
    {
        Schema::create('program_studi', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode', 8)->unique()->comment('Kode singkat prodi, mis. TIF, TSI');
            $tabel->string('nama');
            $tabel->enum('jenjang', ['D3', 'D4', 'S1', 'S2'])->default('S1');
            $tabel->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studi');
    }
};
