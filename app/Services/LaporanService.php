<?php

namespace App\Services;

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use App\Models\TracerStudy;
use Illuminate\Support\Collection;

/**
 * Agregasi data kemahasiswaan untuk Modul Laporan. Dipakai bersama oleh
 * halaman laporan (tampilan) dan controller ekspor (CSV), agar angka konsisten.
 *
 * Catatan portabilitas: memakai ekspresi CASE WHEN (bukan SUM(kondisi)) agar
 * jalan baik di MySQL (produksi) maupun SQLite (pengujian).
 */
class LaporanService
{
    /**
     * Ringkasan tingkat fakultas.
     *
     * @return array{total_mahasiswa:int, mahasiswa_aktif:int, rata_ipk:float|null, total_prestasi:int}
     */
    public function ringkasan(): array
    {
        return [
            'total_mahasiswa' => Mahasiswa::count(),
            'mahasiswa_aktif' => Mahasiswa::where('status', 'aktif')->count(),
            'rata_ipk'        => NilaiIpkSemester::avg('ipk') !== null ? round((float) NilaiIpkSemester::avg('ipk'), 2) : null,
            'total_prestasi'  => Prestasi::count(),
        ];
    }

    /**
     * Rekap per program studi: jumlah mahasiswa, jumlah aktif, rata-rata IPK.
     *
     * @return Collection<int, array{kode:string, nama:string, jumlah:int, aktif:int, rata_ipk:float|null}>
     */
    public function rekapProdi(): Collection
    {
        // Rata-rata IPK per prodi dihitung dari catatan IPK seluruh mahasiswa prodi.
        $rataIpk = NilaiIpkSemester::query()
            ->join('mahasiswa', 'mahasiswa.id', '=', 'nilai_ipk_semester.mahasiswa_id')
            ->selectRaw('mahasiswa.program_studi_id as prodi_id, AVG(nilai_ipk_semester.ipk) as rata')
            ->groupBy('mahasiswa.program_studi_id')
            ->pluck('rata', 'prodi_id');

        return ProgramStudi::query()
            ->withCount([
                'mahasiswa',
                'mahasiswa as aktif_count' => fn ($q) => $q->where('status', 'aktif'),
            ])
            ->orderBy('kode')
            ->get()
            ->map(fn (ProgramStudi $p) => [
                'kode'     => $p->kode,
                'nama'     => $p->nama,
                'jumlah'   => (int) $p->mahasiswa_count,
                'aktif'    => (int) $p->aktif_count,
                'rata_ipk' => isset($rataIpk[$p->id]) ? round((float) $rataIpk[$p->id], 2) : null,
            ]);
    }

    /**
     * Rekap per angkatan: jumlah & aktif.
     *
     * @return Collection<int, array{angkatan:int, jumlah:int, aktif:int}>
     */
    public function rekapAngkatan(): Collection
    {
        return Mahasiswa::query()
            ->selectRaw('angkatan, COUNT(*) as jumlah, SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as aktif', ['aktif'])
            ->groupBy('angkatan')
            ->orderBy('angkatan')
            ->get()
            ->map(fn ($baris) => [
                'angkatan' => (int) $baris->angkatan,
                'jumlah'   => (int) $baris->jumlah,
                'aktif'    => (int) $baris->aktif,
            ]);
    }

    /**
     * Rekap per status studi.
     *
     * @return Collection<int, array{status:string, jumlah:int}>
     */
    public function rekapStatus(): Collection
    {
        return Mahasiswa::query()
            ->selectRaw('status, COUNT(*) as jumlah')
            ->groupBy('status')
            ->orderByDesc('jumlah')
            ->get()
            ->map(fn ($baris) => [
                'status' => (string) $baris->status,
                'jumlah' => (int) $baris->jumlah,
            ]);
    }

    /**
     * Rekap status pekerjaan alumni dari tracer study (selalu 4 kategori).
     *
     * @return Collection<int, array{status:string, label:string, jumlah:int}>
     */
    public function rekapTracer(): Collection
    {
        $peta = [
            'bekerja'       => 'Bekerja',
            'wirausaha'     => 'Wirausaha',
            'lanjut_studi'  => 'Lanjut Studi',
            'belum_bekerja' => 'Belum Bekerja',
        ];

        $hitung = TracerStudy::query()
            ->selectRaw('status_pekerjaan, COUNT(*) as jumlah')
            ->groupBy('status_pekerjaan')
            ->pluck('jumlah', 'status_pekerjaan');

        return collect($peta)
            ->map(fn ($label, $kunci) => [
                'status' => $kunci,
                'label'  => $label,
                'jumlah' => (int) ($hitung[$kunci] ?? 0),
            ])
            ->values();
    }

    /**
     * Rekap prestasi per tingkat (selalu 4 kategori).
     *
     * @return Collection<int, array{tingkat:string, label:string, jumlah:int}>
     */
    public function rekapPrestasiTingkat(): Collection
    {
        $peta = [
            'lokal'         => 'Lokal',
            'regional'      => 'Regional',
            'nasional'      => 'Nasional',
            'internasional' => 'Internasional',
        ];

        $hitung = Prestasi::query()
            ->selectRaw('tingkat, COUNT(*) as jumlah')
            ->groupBy('tingkat')
            ->pluck('jumlah', 'tingkat');

        return collect($peta)
            ->map(fn ($label, $kunci) => [
                'tingkat' => $kunci,
                'label'   => $label,
                'jumlah'  => (int) ($hitung[$kunci] ?? 0),
            ])
            ->values();
    }
}
