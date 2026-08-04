<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel peran (role) + pivot peran→izin, memindahkan peta peran dari
     * config/peran.php ke database agar dapat dikelola Administrator lewat UI.
     *
     * Katalog izin (daftar kode izin yang dikenal sistem) TETAP di config
     * karena terikat pada fitur/kode & nama Gate; yang dipindah ke DB hanya
     * DEFINISI peran dan PENUGASAN izin per peran.
     */
    public function up(): void
    {
        Schema::create('peran', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode', 32)->unique()->comment('Kunci peran, mis. wd3, staf_prodi (dipakai kolom pengguna.peran)');
            $tabel->string('nama');
            $tabel->string('deskripsi')->nullable();
            $tabel->boolean('dilindungi')->default(false)->comment('true = tak dapat dihapus/di-rename (mis. admin)');
            $tabel->boolean('wildcard')->default(false)->comment('true = memiliki SELURUH izin (admin)');
            $tabel->timestamps();
        });

        Schema::create('izin_peran', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('peran_id')->constrained('peran')->cascadeOnUpdate()->cascadeOnDelete();
            $tabel->string('izin_kode', 64)->comment('Kode izin dari katalog config/peran.izin');
            $tabel->timestamps();

            $tabel->unique(['peran_id', 'izin_kode']);
            $tabel->index('izin_kode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('izin_peran');
        Schema::dropIfExists('peran');
    }
};
