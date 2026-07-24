<?php

namespace App\Http\Controllers;

use App\Models\Mahasiswa;
use App\Models\Program;
use App\Services\EvaluatorKelayakan;
use App\Support\HasilKelayakan;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor daftar kandidat hasil penyaringan ke CSV — bahan keputusan manual
 * Wakil Dekan III. Berisi kolom data mentah + status lolos tiap syarat +
 * status kelayakan (boolean). TANPA skor/persentase kecocokan.
 */
class PenyaringanController extends Controller
{
    public function ekspor(Request $request, EvaluatorKelayakan $evaluator): StreamedResponse
    {
        abort_unless($request->user()?->can('program.ekspor'), 403);

        $program = Program::with('syarat')->findOrFail($request->integer('program'));
        $audit = $request->boolean('audit');

        // Sumber kandidat konsisten dengan halaman: pushdown WHERE bila bukan audit.
        $query = $audit
            ? Mahasiswa::query()->with(['programStudi', 'nilaiIpkSemester', 'prestasi', 'kegiatanKemahasiswaan', 'pengabdianHibah'])
            : $evaluator->kandidatQuery($program);

        $query->when($request->integer('prodi'), fn ($q, $id) => $q->where('program_studi_id', $id))
            ->when($request->integer('angkatan'), fn ($q, $a) => $q->where('angkatan', $a));

        $hasil = $evaluator->evaluateProgram($program, $query->get())
            ->filter(fn (HasilKelayakan $h) => $audit ? $h->adaWajibLolos() : $h->layak)
            ->sortBy(fn (HasilKelayakan $h) => $h->mahasiswa->npm)
            ->values();

        $syaratLabel = $program->syarat->map(fn ($s) => $s->label)->all();

        $namaFile = 'kandidat-'.str($program->nama)->slug().'.csv';

        return response()->streamDownload(function () use ($hasil, $syaratLabel) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8 untuk Excel Windows.

            $header = array_merge(
                ['NIM', 'Nama', 'Prodi', 'Angkatan', 'IPK Rata-rata', 'IPK Terakhir',
                    'Skor Prestasi', 'Skor Kegiatan', 'Skor Pengabdian', 'Label Klaster', 'Layak'],
                $syaratLabel,
            );
            fputcsv($handle, $header);

            foreach ($hasil as $h) {
                $m = $h->mahasiswa;
                $status = collect($h->kriteria)
                    ->map(fn ($k) => $k->lolos ? 'Ya' : ($k->keterangan ?: 'Tidak'))
                    ->all();

                fputcsv($handle, array_merge([
                    $m->npm,
                    $m->nama,
                    $m->programStudi?->kode ?? '-',
                    $m->angkatan,
                    number_format($m->ipkRataRata(), 2),
                    number_format($m->ipkTerakhir() ?? 0, 2),
                    $m->skorPrestasi(),
                    $m->skorKegiatan(),
                    $m->skorPengabdian(),
                    '-', // label klaster diisi manual bila diperlukan; hindari query per baris
                    $h->layak ? 'Ya' : 'Tidak',
                ], $status));
            }

            fclose($handle);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
