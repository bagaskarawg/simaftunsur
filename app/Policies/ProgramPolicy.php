<?php

namespace App\Policies;

use App\Models\Pengguna;
use App\Models\Program;

/**
 * Otorisasi modul Penyaringan Kandidat. Mendelegasikan ke izin RBAC
 * (config/peran.php) via Pengguna::punyaIzin — konsisten dengan mekanisme
 * izin proyek (bukan Spatie).
 *
 *   program.lihat  → melihat daftar/detail program
 *   program.kelola → CRUD program & syarat
 *   program.saring → membuka halaman penyaringan kandidat
 *   program.ekspor → mengekspor daftar kandidat
 */
class ProgramPolicy
{
    public function viewAny(Pengguna $pengguna): bool
    {
        return $pengguna->punyaIzin('program.lihat');
    }

    public function view(Pengguna $pengguna, Program $program): bool
    {
        return $pengguna->punyaIzin('program.lihat');
    }

    public function create(Pengguna $pengguna): bool
    {
        return $pengguna->punyaIzin('program.kelola');
    }

    public function update(Pengguna $pengguna, Program $program): bool
    {
        return $pengguna->punyaIzin('program.kelola');
    }

    public function delete(Pengguna $pengguna, Program $program): bool
    {
        return $pengguna->punyaIzin('program.kelola');
    }

    /** Membuka halaman penyaringan kandidat. */
    public function saring(Pengguna $pengguna): bool
    {
        return $pengguna->punyaIzin('program.saring');
    }

    /** Mengekspor daftar kandidat. */
    public function ekspor(Pengguna $pengguna): bool
    {
        return $pengguna->punyaIzin('program.ekspor');
    }
}
