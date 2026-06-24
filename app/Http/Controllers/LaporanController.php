<?php

namespace App\Http\Controllers;

use App\Services\LaporanService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor laporan kemahasiswaan ke CSV. Memakai LaporanService agar angka
 * konsisten dengan tampilan di halaman laporan.
 */
class LaporanController extends Controller
{
    public function eksporProdi(Request $request, LaporanService $laporan): StreamedResponse
    {
        abort_unless($request->user()?->can('laporan.ekspor'), 403);

        $kolom = ['Kode Prodi', 'Program Studi', 'Jumlah Mahasiswa', 'Mahasiswa Aktif', 'Rata-rata IPK'];
        $baris = $laporan->rekapProdi();

        return response()->streamDownload(function () use ($kolom, $baris) {
            $handle = fopen('php://output', 'w');
            // BOM agar Excel di Windows mengenali UTF-8.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $kolom);
            foreach ($baris as $b) {
                fputcsv($handle, [
                    $b['kode'],
                    $b['nama'],
                    $b['jumlah'],
                    $b['aktif'],
                    $b['rata_ipk'] !== null ? number_format($b['rata_ipk'], 2) : '-',
                ]);
            }
            fclose($handle);
        }, 'laporan-rekap-prodi.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
