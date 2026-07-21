<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Template unduh CSV untuk impor data SDM/pengguna massal.
 */
class TemplatePenggunaController extends Controller
{
    public function unduh(Request $request): StreamedResponse
    {
        abort_unless($request->user()?->punyaPeran('admin'), 403);

        $kolom = ['nip', 'nama', 'email', 'peran', 'kata_sandi'];
        $contoh = [
            ['198001012005011001', 'Dr. Andi Wijaya, M.T.', 'andi@ft.unsur.ac.id', 'kaprodi', ''],
            ['199002022010012002', 'Rina Marlina, S.Kom.', 'rina@ft.unsur.ac.id', 'staf_prodi', 'rahasia123'],
        ];

        return response()->streamDownload(function () use ($kolom, $contoh) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $kolom);
            foreach ($contoh as $baris) {
                fputcsv($handle, $baris);
            }
            fclose($handle);
        }, 'template-pengguna-massal.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
