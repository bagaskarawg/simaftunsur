<?php

namespace App\Services;

use App\Models\Mahasiswa;
use Illuminate\Support\Collection;

/**
 * Penyusun daftar KANDIDAT PROGRAM (mawapres, beasiswa, dll) sebagai tindak
 * lanjut hasil klaster: menampilkan langsung identitas mahasiswa yang cocok,
 * DIURUTKAN pada satu ukuran objektif (IPK / poin SKKM).
 *
 * Sengaja "pengurutan biasa" (single sort) — BUKAN pembobotan MCDM. Skor yang
 * dipakai adalah nilai objektif berbasis bukti yang sudah dihitung Model
 * Mahasiswa (ipkRataRata/skorPrestasi/…), bukan komposit preferensi.
 */
class KandidatService
{
    /**
     * Seluruh preset program dari config.
     *
     * @return array<string, array<string, mixed>>
     */
    public function presets(): array
    {
        return (array) config('kandidat.program', []);
    }

    /**
     * Satu preset program, atau null bila kunci tak dikenal.
     *
     * @return array<string, mixed>|null
     */
    public function preset(string $kunci): ?array
    {
        return config("kandidat.program.$kunci");
    }

    /**
     * Daftar kolom pengurutan yang diizinkan (daftar putih objektif).
     *
     * @return array<string, string>
     */
    public function kolomUrut(): array
    {
        return (array) config('kandidat.kolom_urut', []);
    }

    /**
     * Susun daftar kandidat untuk sebuah program.
     *
     * @param  array{prodi_id?: int|null, urut?: string|null, arah?: string|null}  $opsi
     * @return array{
     *     preset: array<string, mixed>,
     *     kunci: string,
     *     kolom_urut: string,
     *     arah: string,
     *     total: int,
     *     ditampilkan: int,
     *     kandidat: Collection<int, array<string, mixed>>
     * }
     */
    public function daftar(string $programKunci, array $opsi = []): array
    {
        $presetTersedia = $this->presets();
        $kunci = isset($presetTersedia[$programKunci])
            ? $programKunci
            : (array_key_first($presetTersedia) ?? '');
        $preset = $presetTersedia[$kunci] ?? [];

        $ipkMin = (float) ($preset['syarat']['ipk_min'] ?? 0.0);
        $butuhPrestasi = (bool) ($preset['syarat']['butuh_prestasi'] ?? false);

        // Kolom & arah pengurutan: pakai pilihan pengguna bila valid, jika tidak
        // pakai bawaan preset. Dibatasi daftar putih agar objektif & aman.
        $kolomValid = array_keys($this->kolomUrut());
        $kolomUrut = in_array($opsi['urut'] ?? null, $kolomValid, true)
            ? $opsi['urut']
            : ($preset['urut']['kolom'] ?? 'ipk_rata_rata');
        $arah = in_array($opsi['arah'] ?? null, ['asc', 'desc'], true)
            ? $opsi['arah']
            : ($preset['urut']['arah'] ?? 'desc');

        // Hanya mahasiswa AKTIF (subjek pembinaan berjalan), plus filter prodi.
        $query = Mahasiswa::query()
            ->where('status', 'aktif')
            ->with(['programStudi', 'nilaiIpkSemester', 'prestasi', 'kegiatanKemahasiswaan', 'pengabdianHibah']);

        if (! empty($opsi['prodi_id'])) {
            $query->where('program_studi_id', $opsi['prodi_id']);
        }

        $baris = $query->get()
            ->map(fn (Mahasiswa $m) => $this->petakanBaris($m))
            // Penyaringan syarat dasar program.
            ->filter(fn (array $b) => $b['ipk_rata_rata'] >= $ipkMin)
            ->when($butuhPrestasi, fn (Collection $c) => $c->filter(fn (array $b) => $b['skor_prestasi'] > 0))
            ->values();

        // Pengurutan tunggal + tie-breaker IPK rata-rata (stabil & masuk akal).
        $baris = $arah === 'asc'
            ? $baris->sortBy([[$kolomUrut, 'asc'], ['ipk_rata_rata', 'asc']])->values()
            : $baris->sortByDesc(fn (array $b) => [$b[$kolomUrut], $b['ipk_rata_rata']])->values();

        $total = $baris->count();
        $batas = (int) config('kandidat.batas_tampil', 100);

        return [
            'preset' => $preset,
            'kunci' => $kunci,
            'kolom_urut' => $kolomUrut,
            'arah' => $arah,
            'total' => $total,
            'ditampilkan' => min($total, $batas),
            'kandidat' => $baris->take($batas),
        ];
    }

    /**
     * Petakan satu mahasiswa menjadi baris kandidat berisi ukuran objektif.
     *
     * @return array<string, mixed>
     */
    protected function petakanBaris(Mahasiswa $mahasiswa): array
    {
        $prestasi = $mahasiswa->skorPrestasi();
        $kegiatan = $mahasiswa->skorKegiatan();
        $pengabdian = $mahasiswa->skorPengabdian();

        return [
            'mahasiswa' => $mahasiswa,
            'npm' => $mahasiswa->npm,
            'nama' => $mahasiswa->nama,
            'prodi' => $mahasiswa->programStudi?->kode,
            'semester_aktif' => $mahasiswa->semester_aktif,
            'ipk_rata_rata' => $mahasiswa->ipkRataRata(),
            'ipk_terakhir' => $mahasiswa->ipkTerakhir() ?? 0.0,
            'skor_prestasi' => $prestasi,
            'skor_kegiatan' => $kegiatan,
            'skor_pengabdian' => $pengabdian,
            'skor_non_akademik' => $prestasi + $kegiatan + $pengabdian,
        ];
    }
}
