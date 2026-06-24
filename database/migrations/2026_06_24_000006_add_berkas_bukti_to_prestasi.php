<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom berkas_bukti (path file unggahan) pada prestasi, melengkapi
     * url_bukti yang berupa tautan.
     */
    public function up(): void
    {
        Schema::table('prestasi', function (Blueprint $tabel) {
            $tabel->string('berkas_bukti')->nullable()->after('url_bukti');
        });
    }

    public function down(): void
    {
        Schema::table('prestasi', function (Blueprint $tabel) {
            $tabel->dropColumn('berkas_bukti');
        });
    }
};
