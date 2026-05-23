<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrasi: membuat tabel pengguna, token reset kata sandi,
     * dan tabel sesi.
     */
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->string('nip', 32)->unique()->comment('NIP/NIDN sebagai identitas masuk');
            $tabel->string('nama');
            $tabel->string('email')->unique()->nullable();
            $tabel->string('kata_sandi');
            $tabel->string('peran', 32)->default('staf')->comment('admin|dekan|wd3|kaprodi|staf|dosen');
            $tabel->timestamp('email_terverifikasi_pada')->nullable();
            $tabel->rememberToken();
            $tabel->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $tabel) {
            $tabel->string('email')->primary();
            $tabel->string('token');
            $tabel->timestamp('created_at')->nullable();
        });

        // Catatan: kolom user_id pada tabel sessions dipertahankan dalam Bahasa Inggris
        // karena Illuminate\Session\DatabaseSessionHandler meng-hard-code nama kolom ini.
        Schema::create('sessions', function (Blueprint $tabel) {
            $tabel->string('id')->primary();
            $tabel->foreignId('user_id')->nullable()->index();
            $tabel->string('ip_address', 45)->nullable();
            $tabel->text('user_agent')->nullable();
            $tabel->longText('payload');
            $tabel->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('pengguna');
    }
};
