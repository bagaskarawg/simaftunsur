<?php

namespace App\Imports;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Impor data MAHASISWA secara massal dari satu file CSV/XLSX.
 *
 * Format kolom yang diharapkan (case-insensitive, baris pertama header):
 *   npm, nama, prodi, angkatan, semester_aktif, jenis_kelamin, status,
 *   email, nomor_telepon
 *
 * Aturan:
 *  - `prodi` diisi KODE prodi (mis. 55201). Kode tidak terdaftar → baris gagal.
 *  - Upsert berdasarkan NPM: NPM yang sudah ada akan diperbarui (ditimpa).
 *  - `status` opsional, default "aktif". `email`/`nomor_telepon` opsional.
 *  - Baris yang gagal divalidasi dilewati & dilaporkan, tidak membatalkan
 *    baris lain yang valid.
 */
class MahasiswaMassalImport implements ToCollection, WithHeadingRow
{
    public HasilImpor $hasil;

    /** @var array<string, int>  Cache lookup KODE prodi → program_studi_id */
    protected array $petaProdi = [];

    public function __construct()
    {
        $this->hasil = new HasilImpor();
    }

    public function collection(Collection $baris): void
    {
        // Peta kode prodi (uppercase) → id, dibangun sekali agar hemat kuery.
        $this->petaProdi = ProgramStudi::query()
            ->pluck('id', 'kode')
            ->mapWithKeys(fn ($id, $kode) => [strtoupper(trim((string) $kode)) => $id])
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

            $prodiId = $this->petaProdi[strtoupper($data['prodi'])] ?? null;
            if (! $prodiId) {
                $this->hasil->gagal[] = [
                    'baris' => $nomorBaris,
                    'pesan' => "Kode prodi \"{$data['prodi']}\" tidak terdaftar.",
                ];
                continue;
            }

            $eksisting = Mahasiswa::where('npm', $data['npm'])->first();

            Mahasiswa::updateOrCreate(
                ['npm' => $data['npm']],
                [
                    'nama'             => $data['nama'],
                    'program_studi_id' => $prodiId,
                    'angkatan'         => $data['angkatan'],
                    'semester_aktif'   => $data['semester_aktif'],
                    'jenis_kelamin'    => $data['jenis_kelamin'],
                    'status'           => $data['status'],
                    'email'            => $data['email'],
                    'nomor_telepon'    => $data['nomor_telepon'],
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

        return [
            'npm'            => trim((string) ($cari(['npm', 'nim', 'nomor_pokok']) ?? '')),
            'nama'           => trim((string) ($cari(['nama', 'nama_mahasiswa']) ?? '')),
            'prodi'          => trim((string) ($cari(['prodi', 'program_studi', 'kode_prodi']) ?? '')),
            'angkatan'       => is_numeric($cari(['angkatan', 'tahun_masuk'])) ? (int) $cari(['angkatan', 'tahun_masuk']) : null,
            'semester_aktif' => is_numeric($cari(['semester_aktif', 'semester'])) ? (int) $cari(['semester_aktif', 'semester']) : null,
            'jenis_kelamin'  => $this->normalisasiJenisKelamin($cari(['jenis_kelamin', 'jk', 'gender'])),
            'status'         => $this->normalisasiStatus($cari(['status', 'status_mahasiswa'])),
            'email'          => ($e = trim((string) ($cari(['email', 'surel']) ?? ''))) !== '' ? $e : null,
            'nomor_telepon'  => ($t = trim((string) ($cari(['nomor_telepon', 'no_telepon', 'hp', 'telepon']) ?? ''))) !== '' ? $t : null,
        ];
    }

    /**
     * Normalisasi jenis kelamin ke 'L' / 'P'. Mengembalikan null bila tak dikenal
     * agar validasi menolaknya dengan pesan jelas.
     */
    protected function normalisasiJenisKelamin(mixed $nilai): ?string
    {
        $v = strtolower(trim((string) ($nilai ?? '')));

        return match (true) {
            in_array($v, ['l', 'laki', 'laki-laki', 'pria', 'male', 'm'], true) => 'L',
            in_array($v, ['p', 'perempuan', 'wanita', 'female', 'f'], true)      => 'P',
            default                                                              => null,
        };
    }

    /**
     * Normalisasi status studi; kosong → 'aktif'. Spasi/tanda hubung → underscore.
     */
    protected function normalisasiStatus(mixed $nilai): string
    {
        $v = strtolower(trim((string) ($nilai ?? '')));
        if ($v === '') {
            return 'aktif';
        }

        return str_replace([' ', '-'], '_', $v);
    }

    /**
     * @return array<string, mixed>
     */
    protected function aturan(): array
    {
        return [
            'npm'            => ['required', 'string', 'size:11'],
            'nama'           => ['required', 'string', 'max:255'],
            'prodi'          => ['required', 'string'],
            'angkatan'       => ['required', 'integer', 'between:2000,2100'],
            'semester_aktif' => ['required', 'integer', 'between:1,14'],
            'jenis_kelamin'  => ['required', 'in:L,P'],
            'status'         => ['required', 'in:aktif,cuti,non_aktif,lulus,do'],
            'email'          => ['nullable', 'email', 'max:255'],
            'nomor_telepon'  => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function pesan(): array
    {
        return [
            'npm.required'           => 'Kolom NPM wajib diisi.',
            'npm.size'               => 'NPM harus 11 karakter.',
            'nama.required'          => 'Kolom nama wajib diisi.',
            'prodi.required'         => 'Kolom prodi (kode) wajib diisi.',
            'angkatan.between'       => 'Angkatan harus tahun 4 digit yang wajar.',
            'semester_aktif.between' => 'Semester aktif harus 1-14.',
            'jenis_kelamin.in'       => 'Jenis kelamin harus L atau P (atau "laki-laki"/"perempuan").',
            'status.in'              => 'Status harus salah satu: aktif, cuti, non_aktif, lulus, do.',
            'email.email'            => 'Format email tidak valid.',
        ];
    }
}
