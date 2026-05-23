<?php

namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Impor riwayat IPK untuk BANYAK mahasiswa sekaligus.
 *
 * Format kolom yang diharapkan (case-insensitive):
 *   npm, semester, tahun_akademik, ganjil_genap, ipk, sks_diambil, sks_lulus
 *
 * Aturan:
 *  - NPM dipakai untuk menemukan mahasiswa. NPM tidak terdaftar → baris gagal.
 *  - Upsert berdasarkan (mahasiswa_id, semester).
 *  - Map NPM → id di-cache supaya tidak query DB per baris.
 */
class IpkMassalImport implements ToCollection, WithHeadingRow
{
    public HasilImpor $hasil;

    /** @var array<string, int>  Cache lookup NPM → mahasiswa_id */
    protected array $petaNpm = [];

    public function __construct()
    {
        $this->hasil = new HasilImpor();
    }

    public function collection(Collection $baris): void
    {
        // Build map NPM → id sekali di awal. Lebih hemat kuery dibanding
        // memanggil Mahasiswa::where('npm', ...) per baris.
        $this->petaNpm = Mahasiswa::query()
            ->pluck('id', 'npm')
            ->mapWithKeys(fn ($id, $npm) => [trim((string) $npm) => $id])
            ->all();

        foreach ($baris as $indeks => $row) {
            $nomorBaris = $indeks + 2;

            $data = $this->normalisasiBaris($row->toArray());

            $validator = Validator::make($data, $this->aturan(), $this->pesan());
            if ($validator->fails()) {
                $this->hasil->gagal[] = [
                    'baris' => $nomorBaris,
                    'pesan' => collect($validator->errors()->all())->implode('; '),
                ];
                continue;
            }

            $mahasiswaId = $this->petaNpm[$data['npm']] ?? null;
            if (! $mahasiswaId) {
                $this->hasil->gagal[] = [
                    'baris' => $nomorBaris,
                    'pesan' => "NPM {$data['npm']} tidak terdaftar.",
                ];
                continue;
            }

            $eksisting = NilaiIpkSemester::where('mahasiswa_id', $mahasiswaId)
                ->where('semester', $data['semester'])
                ->first();

            NilaiIpkSemester::updateOrCreate(
                [
                    'mahasiswa_id' => $mahasiswaId,
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
            'npm'            => trim((string) ($cari(['npm', 'nim', 'nomor_pokok']) ?? '')),
            'semester'       => is_numeric($cari(['semester'])) ? (int) $cari(['semester']) : null,
            'tahun_akademik' => trim((string) ($cari(['tahun_akademik', 'tahunakademik', 'ta']) ?? '')),
            'ganjil_genap'   => in_array($ganjilGenap, ['ganjil', 'genap'], true) ? $ganjilGenap : null,
            'ipk'            => is_numeric($cari(['ipk'])) ? round((float) $cari(['ipk']), 2) : null,
            'sks_diambil'    => is_numeric($cari(['sks_diambil', 'sksdiambil'])) ? (int) $cari(['sks_diambil', 'sksdiambil']) : null,
            'sks_lulus'      => is_numeric($cari(['sks_lulus', 'sks_lolos'])) ? (int) $cari(['sks_lulus', 'sks_lolos']) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function aturan(): array
    {
        return [
            'npm'            => ['required', 'string', 'size:11'],
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
            'npm.required'         => 'Kolom NPM wajib diisi.',
            'npm.size'             => 'NPM harus 11 karakter.',
            'semester.between'     => 'Semester harus 1-14.',
            'tahun_akademik.regex' => 'Format tahun akademik harus YYYY/YYYY.',
            'ganjil_genap.in'      => 'Kolom ganjil_genap harus "ganjil" atau "genap".',
            'ipk.between'          => 'IPK harus 0.00-4.00.',
            'sks_lulus.lte'        => 'SKS lulus tidak boleh melebihi SKS diambil.',
        ];
    }
}
