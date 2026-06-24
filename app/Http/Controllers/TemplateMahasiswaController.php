<?php

namespace App\Http\Controllers;

use App\Models\ProgramStudi;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Template unduh CSV untuk impor data mahasiswa massal. Dibangkitkan
 * on-the-fly agar header selalu konsisten dengan MahasiswaMassalImport.
 * Kode prodi pada baris contoh diambil dari prodi yang benar-benar ada.
 */
class TemplateMahasiswaController extends Controller
{
    public function unduh(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('mahasiswa.kelola'), 403);

        $kolom = ['npm', 'nama', 'prodi', 'angkatan', 'semester_aktif', 'jenis_kelamin', 'status', 'email', 'nomor_telepon'];

        $kodeProdi = ProgramStudi::query()->orderBy('kode')->value('kode') ?? 'TIF';

        $contoh = [
            ['20200000001', 'Budi Santoso', $kodeProdi, 2020, 7, 'L', 'aktif', 'budi@example.ac.id', '081234567890'],
            ['20200000002', 'Siti Aminah', $kodeProdi, 2020, 7, 'P', 'aktif', '', ''],
        ];

        return response()->streamDownload(function () use ($kolom, $contoh) {
            $handle = fopen('php://output', 'w');
            // BOM agar Excel di Windows mengenali UTF-8.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $kolom);
            foreach ($contoh as $baris) {
                fputcsv($handle, $baris);
            }
            fclose($handle);
        }, 'template-mahasiswa-massal.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
