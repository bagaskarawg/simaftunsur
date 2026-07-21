<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Master "Kategori Klaster" — katalog nama/label yang dipetakan ke klaster
 * hasil K-Means berdasarkan PERINGKAT skor komposit (bukan menetapkan jumlah
 * klaster; jumlah tetap dinamis dari algoritma). Dikelola WD III/Admin dan
 * dikirim ke service Python sebagai `konfigurasi_label.katalog`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klasterisasi_kategori', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nama')->unique();
            $tabel->unsignedTinyInteger('urutan')->default(1)
                ->comment('Peringkat skor komposit: 1 = tertinggi (mis. Berprestasi)');
            $tabel->text('deskripsi')->nullable();
            $tabel->text('rekomendasi')->nullable()->comment('Rekomendasi pembinaan untuk kategori ini');
            $tabel->string('warna', 16)->nullable()->comment('Token warna dashboard, mis. cluster-1');
            $tabel->boolean('aktif')->default(true);
            $tabel->timestamps();

            $tabel->index('urutan');
            $tabel->index('aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klasterisasi_kategori');
    }
};
