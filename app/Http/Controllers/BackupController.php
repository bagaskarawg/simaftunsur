<?php

namespace App\Http\Controllers;

use App\Models\KegiatanPromosi;
use App\Models\KlasterisasiAnggota;
use App\Models\KlasterisasiEksekusi;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengaturan;
use App\Models\Pengguna;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use App\Models\TracerStudy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Backup data: mengekspor seluruh data bisnis ke satu berkas JSON
 * (portable, tanpa binary eksternal). Khusus Administrator.
 *
 * Catatan: data pengguna diekspor TANPA kata sandi/token demi keamanan —
 * berkas ini cadangan data, bukan dump kredensial.
 */
class BackupController extends Controller
{
    public function unduh(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->punyaPeran('admin'), 403);

        $data = [
            'meta' => [
                'aplikasi' => 'SIMAFTUNSUR',
                'dicetak'  => now()->toIso8601String(),
                'oleh'     => $request->user()?->nama,
            ],
            'program_studi'         => ProgramStudi::all()->toArray(),
            'mahasiswa'             => Mahasiswa::all()->toArray(),
            'nilai_ipk_semester'    => NilaiIpkSemester::all()->toArray(),
            'prestasi'              => Prestasi::all()->toArray(),
            'tracer_study'          => TracerStudy::all()->toArray(),
            'kegiatan_promosi'      => KegiatanPromosi::all()->toArray(),
            'klasterisasi_eksekusi' => KlasterisasiEksekusi::all()->toArray(),
            'klasterisasi_anggota'  => KlasterisasiAnggota::all()->toArray(),
            'pengaturan'            => Pengaturan::all()->toArray(),
            'pengguna'              => Pengguna::all()
                ->map(fn ($p) => $p->only(['id', 'nip', 'nama', 'email', 'peran']))
                ->toArray(),
        ];

        $namaFile = 'backup-simaftunsur-'.now()->format('Ymd-His').'.json';

        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $namaFile, [
            'Content-Type' => 'application/json; charset=UTF-8',
        ]);
    }
}
