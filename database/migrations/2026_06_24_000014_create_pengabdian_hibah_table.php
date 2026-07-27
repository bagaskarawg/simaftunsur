<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengabdian masyarakat & hibah/PKM — sumber Skor Pengabdian (fitur F7).
     * Modul pendukung CRUD; poin dihitung dari rubrik SKKM (config/skkm.php)
     * berdasarkan pasangan (jenis, peran).
     */
    public function up(): void
    {
        Schema::create('pengabdian_hibah', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->foreignId('mahasiswa_id')
                ->constrained('mahasiswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $tabel->enum('jenis', ['pimnas', 'hibah_didanai', 'proposal_lolos', 'pengabdian_masyarakat']);
            $tabel->string('peran')->comment('ketua/anggota (hibah/proposal) atau peserta_aktif (pengabdian)');
            $tabel->string('judul');
            $tabel->string('sumber_dana')->nullable()->comment('mis. Kemendikti, Fakultas, Mandiri');
            $tabel->year('tahun')->nullable();
            $tabel->string('url_bukti')->nullable();
            $tabel->text('keterangan')->nullable();
            $tabel->timestamps();

            $tabel->index('mahasiswa_id');
            $tabel->index('jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengabdian_hibah');
    }
};
