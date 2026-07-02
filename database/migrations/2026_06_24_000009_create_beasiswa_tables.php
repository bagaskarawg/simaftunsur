<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel modul Beasiswa — modul pendukung CRUD (non-fokus, tanpa sistem
     * cerdas). Terdiri atas master kategori beasiswa dan daftar penerima
     * beasiswa yang terhubung ke mahasiswa.
     *
     * Catatan: skema lama (codebase native) memisahkan tabel "calon/usulan"
     * dan "penerima". Di sini keduanya disatukan dalam satu tabel penerima
     * dengan kolom `status` bersiklus penuh (diusulkan → ditetapkan/ditolak →
     * selesai/dibekukan) agar lebih ringkas dan bebas duplikasi.
     */
    public function up(): void
    {
        Schema::create('beasiswa_kategori', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('kode', 30)->unique();
            $tabel->string('nama');
            $tabel->enum('jenis_bantuan', ['ukt', 'biaya_hidup', 'total'])->default('ukt');
            $tabel->enum('sumber_dana', ['ftunsur', 'lldikti', 'kemendikti'])->default('ftunsur');
            $tabel->boolean('aktif')->default(true);
            $tabel->text('keterangan')->nullable();
            $tabel->timestamps();

            $tabel->index('aktif');
        });

        Schema::create('beasiswa_penerima', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->foreignId('beasiswa_kategori_id')
                ->constrained('beasiswa_kategori')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $tabel->string('tahun_akademik', 9)->comment("mis. '2025/2026'");
            $tabel->enum('semester', ['ganjil', 'genap'])->default('ganjil');
            $tabel->enum('status', [
                'diusulkan',
                'diverifikasi',
                'ditetapkan',
                'ditolak',
                'selesai',
                'dibekukan',
            ])->default('diusulkan');
            $tabel->decimal('nominal', 12, 2)->nullable();
            $tabel->string('no_sk')->nullable();
            $tabel->date('tanggal_sk')->nullable();
            $tabel->string('sumber_usulan')->nullable()->comment('mis. Prodi, Fakultas, Mandiri');
            $tabel->text('keterangan')->nullable();
            $tabel->timestamps();

            $tabel->index('status');
            $tabel->index('tahun_akademik');
            $tabel->index('mahasiswa_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beasiswa_penerima');
        Schema::dropIfExists('beasiswa_kategori');
    }
};
