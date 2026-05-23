<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menyediakan template unduh CSV untuk pengguna agar tahu kolom apa
 * saja yang diharapkan saat impor IPK. Sengaja dibangkitkan on-the-fly
 * (bukan file statis) supaya konsisten dengan header yang dipakai
 * import class — kalau aturan kolom berubah, template ikut berubah.
 */
class TemplateIpkController extends Controller
{
    public function unduh(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->can('mahasiswa.kelola'), 403);

        $mode = $request->query('mode', 'massal');
        $isMassal = $mode === 'massal';

        $kolom = $isMassal
            ? ['npm', 'semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus']
            : ['semester', 'tahun_akademik', 'ganjil_genap', 'ipk', 'sks_diambil', 'sks_lulus'];

        $contoh = $isMassal
            ? [
                ['20200000001', 1, '2020/2021', 'ganjil', 3.45, 22, 22],
                ['20200000001', 2, '2020/2021', 'genap',  3.52, 24, 24],
                ['20200000002', 1, '2020/2021', 'ganjil', 3.10, 20, 18],
            ]
            : [
                [1, '2020/2021', 'ganjil', 3.45, 22, 22],
                [2, '2020/2021', 'genap',  3.52, 24, 24],
                [3, '2021/2022', 'ganjil', 3.60, 22, 22],
            ];

        $namaFile = $isMassal ? 'template-ipk-massal.csv' : 'template-ipk-satu-mahasiswa.csv';

        return response()->streamDownload(function () use ($kolom, $contoh) {
            $handle = fopen('php://output', 'w');
            // BOM agar Excel di Windows mengenali UTF-8.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $kolom);
            foreach ($contoh as $baris) {
                fputcsv($handle, $baris);
            }
            fclose($handle);
        }, $namaFile, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
