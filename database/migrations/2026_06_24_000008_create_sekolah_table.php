<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Sekolah target promosi/PMB — perluasan modul Promosi.
     */
    public function up(): void
    {
        Schema::create('sekolah', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama');
            $tabel->enum('jenjang', ['SMA', 'SMK', 'MA', 'Lainnya'])->default('SMA');
            $tabel->string('kota')->nullable();
            $tabel->string('alamat')->nullable();
            $tabel->string('kontak')->nullable();
            $tabel->timestamps();

            $tabel->index('kota');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sekolah');
    }
};
