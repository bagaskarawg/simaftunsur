<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menstrukturkan capaian prestasi agar Skor Prestasi (fitur F5) dapat
     * dihitung objektif dari rubrik SKKM (config/skkm.php). Kolom `peringkat`
     * yang lama tetap ada sebagai deskripsi bebas; `capaian` yang menentukan poin.
     */
    public function up(): void
    {
        Schema::table('prestasi', function (Blueprint $tabel) {
            $tabel->enum('capaian', ['juara_1', 'juara_2', 'juara_3', 'finalis'])
                ->nullable()
                ->after('tingkat')
                ->comment('Capaian terstruktur untuk skor SKKM (F5); null = tidak berpoin');
        });
    }

    public function down(): void
    {
        Schema::table('prestasi', function (Blueprint $tabel) {
            $tabel->dropColumn('capaian');
        });
    }
};
