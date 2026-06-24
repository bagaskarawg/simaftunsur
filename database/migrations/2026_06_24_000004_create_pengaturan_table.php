<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel pengaturan sistem (key-value) — menyimpan konfigurasi ringan
     * seperti periode akademik aktif & identitas fakultas. Sengaja key-value
     * agar mudah diperluas tanpa migrasi baru.
     */
    public function up(): void
    {
        Schema::create('pengaturan', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kunci')->unique();
            $tabel->text('nilai')->nullable();
            $tabel->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan');
    }
};
