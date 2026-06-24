<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log aktivitas pengguna — jejak audit ringan (dibuat/diubah/dihapus/
     * masuk/keluar) atas entitas bisnis utama.
     */
    public function up(): void
    {
        Schema::create('log_aktivitas', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('pengguna_id')->nullable()->constrained('pengguna')->nullOnDelete();
            $tabel->string('aksi', 32);
            $tabel->string('model', 64)->nullable();
            $tabel->string('deskripsi')->nullable();
            $tabel->string('ip', 45)->nullable();
            $tabel->timestamps();

            $tabel->index('created_at');
            $tabel->index('model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_aktivitas');
    }
};
