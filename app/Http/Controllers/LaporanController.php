<?php

namespace App\Http\Controllers;

use App\Services\LaporanService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor laporan kemahasiswaan ke CSV & PDF. Memakai LaporanService agar
 * angka konsisten dengan tampilan di halaman laporan.
 */
class LaporanController extends Controller
{
    /**
     * Ekspor seluruh rekap kemahasiswaan ke PDF (siap cetak/lampiran).
     */
    public function eksporPdf(Request $request, LaporanService $laporan): Response
    {
        abort_unless($request->user()?->can('laporan.ekspor'), 403);

        $pdf = Pdf::loadView('laporan.pdf', [
            'ringkasan' => $laporan->ringkasan(),
            'prodi'     => $laporan->rekapProdi(),
            'status'    => $laporan->rekapStatus(),
            'tracer'    => $laporan->rekapTracer(),
            'tingkat'   => $laporan->rekapPrestasiTingkat(),
            'dicetak'   => now()->format('d-m-Y H:i'),
            'oleh'      => $request->user()?->nama,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('laporan-kemahasiswaan.pdf');
    }

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
