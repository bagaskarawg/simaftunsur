<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah profil ekonomi/orang tua ke mahasiswa. Dipakai sebagai kriteria
     * PENYARINGAN KANDIDAT (mis. beasiswa: kategori ekonomi rendah/menengah) dan
     * atribut profil — BUKAN fitur klasterisasi (7 fitur K-Means tetap terkunci).
     */
    public function up(): void
    {
        Schema::table('mahasiswa', function (Blueprint $tabel) {
            $tabel->unsignedBigInteger('penghasilan_orang_tua')->nullable()
                ->after('nomor_telepon')->comment('Total penghasilan orang tua/wali per bulan (Rupiah)');
            $tabel->enum('kategori_ekonomi', ['rendah', 'menengah', 'tinggi'])->nullable()
                ->after('penghasilan_orang_tua')->comment('Kategori ekonomi untuk penyaringan (mis. beasiswa)');
            $tabel->string('pekerjaan_orang_tua')->nullable()
                ->after('kategori_ekonomi');

            $tabel->index('kategori_ekonomi');
        });
    }

    public function down(): void
    {
        Schema::table('mahasiswa', function (Blueprint $tabel) {
            $tabel->dropIndex(['kategori_ekonomi']);
            $tabel->dropColumn(['penghasilan_orang_tua', 'kategori_ekonomi', 'pekerjaan_orang_tua']);
        });
    }
};
