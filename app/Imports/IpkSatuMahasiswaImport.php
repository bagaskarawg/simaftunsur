<?php

namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Impor riwayat IPK untuk SATU mahasiswa (konteks sudah dipilih lewat UI).
 *
 * Format kolom yang diharapkan (header baris pertama, case-insensitive):
 *   semester, tahun_akademik, ganjil_genap, ipk, sks_diambil, sks_lulus
 *
 * Aturan:
 *  - Upsert berdasarkan (mahasiswa_id, semester) — data lama ditimpa bila cocok.
 *  - Baris tidak valid (validasi gagal) dicatat ke $hasil->gagal[],
 *    tidak menghentikan proses baris lain.
 */
class IpkSatuMahasiswaImport implements ToCollection, WithHeadingRow
{
    public HasilImpor $hasil;

    public function __construct(public Mahasiswa $mahasiswa)
    {
        $this->hasil = new HasilImpor();
    }

    public function collection(Collection $baris): void
    {
        foreach ($baris as $indeks => $row) {
            $nomorBaris = $indeks + 2; // +1 header, +1 base-1

            $data = $this->normalisasiBaris($row->toArray());

            $validator = Validator::make($data, $this->aturan(), $this->pesan());
            if ($validator->fails()) {
                $this->hasil->gagal[] = [
                    'baris' => $nomorBaris,
                    'pesan' => collect($validator->errors()->all())->implode('; '),
                ];
                continue;
            }

            $eksisting = NilaiIpkSemester::where('mahasiswa_id', $this->mahasiswa->id)
                ->where('semester', $data['semester'])
                ->first();

            NilaiIpkSemester::updateOrCreate(
                [
                    'mahasiswa_id' => $this->mahasiswa->id,
                    'semester'     => $data['semester'],
                ],
                [
                    'tahun_akademik'        => $data['tahun_akademik'],
                    'semester_ganjil_genap' => $data['ganjil_genap'],
                    'ipk'                   => $data['ipk'],
                    'sks_diambil'           => $data['sks_diambil'],
                    'sks_lulus'             => $data['sks_lulus'],
                ],
            );

            $eksisting ? $this->hasil->ditimpa++ : $this->hasil->ditambah++;
        }
    }

    /**
     * Normalisasi penamaan kolom: header bisa "Semester", "SEMESTER", "ipk_rata",
     * dsb. Maatwebsite sudah mengubah ke snake_case lowercase via WithHeadingRow,
     * tapi kita tetap defensif untuk variasi nama.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    protected function normalisasiBaris(array $row): array
    {
        $cari = fn (array $kandidat) => collect($kandidat)
            ->map(fn ($k) => $row[$k] ?? null)
            ->first(fn ($v) => $v !== null && $v !== '');

        $ganjilGenap = strtolower(trim((string) ($cari(['ganjil_genap', 'semester_ganjil_genap', 'periode']) ?? '')));

        return [
            'semester'       => is_numeric($cari(['semester'])) ? (int) $cari(['semester']) : null,
            'tahun_akademik' => trim((string) ($cari(['tahun_akademik', 'tahunakademik', 'ta']) ?? '')),
            'ganjil_genap'   => in_array($ganjilGenap, ['ganjil', 'genap'], true) ? $ganjilGenap : null,
            'ipk'            => is_numeric($cari(['ipk'])) ? round((float) $cari(['ipk']), 2) : null,
            'sks_diambil'    => is_numeric($cari(['sks_diambil', 'sksdiambil'])) ? (int) $cari(['sks_diambil', 'sksdiambil']) : null,
            'sks_lulus'      => is_numeric($cari(['sks_lulus', 'sks_lolos', 'sksk'])) ? (int) $cari(['sks_lulus', 'sks_lolos', 'sksk']) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function aturan(): array
    {
        return [
            'semester'       => ['required', 'integer', 'between:1,14'],
            'tahun_akademik' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
            'ganjil_genap'   => ['required', 'in:ganjil,genap'],
            'ipk'            => ['required', 'numeric', 'between:0,4'],
            'sks_diambil'    => ['required', 'integer', 'between:0,30'],
            'sks_lulus'      => ['required', 'integer', 'between:0,30', 'lte:sks_diambil'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function pesan(): array
    {
        return [
            'semester.required'       => 'Kolom semester wajib diisi.',
            'semester.between'        => 'Semester harus 1-14.',
            'tahun_akademik.regex'    => 'Format tahun akademik harus YYYY/YYYY (mis. 2025/2026).',
            'ganjil_genap.in'         => 'Kolom ganjil_genap harus "ganjil" atau "genap".',
            'ipk.between'             => 'IPK harus 0.00-4.00.',
            'sks_lulus.lte'           => 'SKS lulus tidak boleh melebihi SKS diambil.',
        ];
    }
}
