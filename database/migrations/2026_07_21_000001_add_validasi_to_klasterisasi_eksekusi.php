<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan hasil VALIDASI LANJUTAN klaster (pelengkap metrik internal):
     *
     *  1. `stabilitas`       — hasil uji stabilitas bootstrap-Jaccard (JSON):
     *     skor per klaster, rata-rata, minimum, & kategori keseluruhan.
     *  2. `stabilitas_rata`  — rata-rata skor Jaccard (ringkas untuk KPI/urut).
     *  3. `uji_beda`         — hasil uji beda antar-klaster Kruskal-Wallis (JSON):
     *     statistik H & p-value per fitur.
     *
     * Kolom nullable agar eksekusi lama (sebelum fitur ini) tetap valid.
     */
    public function up(): void
    {
        Schema::table('klasterisasi_eksekusi', function (Blueprint $tabel) {
            $tabel->json('stabilitas')->nullable()->after('profil_klaster')
                ->comment('Uji stabilitas bootstrap-Jaccard (per klaster + ringkasan)');
            $tabel->double('stabilitas_rata')->nullable()->after('stabilitas')
                ->comment('Rata-rata skor Jaccard antar-klaster (0..1)');
            $tabel->json('uji_beda')->nullable()->after('stabilitas_rata')
                ->comment('Uji beda antar-klaster Kruskal-Wallis (H & p-value per fitur)');
        });
    }

    public function down(): void
    {
        Schema::table('klasterisasi_eksekusi', function (Blueprint $tabel) {
            $tabel->dropColumn(['stabilitas', 'stabilitas_rata', 'uji_beda']);
        });
    }
};
