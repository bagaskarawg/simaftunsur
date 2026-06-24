<?php

namespace App\Imports;

use App\Models\Pengguna;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Impor data SDM (pengguna: dosen/staf/dll.) secara massal.
 *
 * Kolom diharapkan: nip, nama, email, peran, kata_sandi
 *  - Upsert berdasarkan NIP.
 *  - peran harus salah satu kunci di config/peran.php.
 *  - kata_sandi opsional; bila kosong saat membuat akun baru, dipakai
 *    kata sandi default (lihat KATA_SANDI_DEFAULT) — minta pengguna menggantinya.
 */
class PenggunaMassalImport implements ToCollection, WithHeadingRow
{
    public const KATA_SANDI_DEFAULT = 'rahasia123';

    public HasilImpor $hasil;

    /** @var array<int, string> */
    protected array $peranValid;

    public function __construct()
    {
        $this->hasil = new HasilImpor();
        $this->peranValid = array_keys((array) config('peran.peta', []));
    }

    public function collection(Collection $baris): void
    {
        foreach ($baris as $indeks => $row) {
            $nomorBaris = $indeks + 2;
            $data = $this->normalisasiBaris($row->toArray());

            $validator = Validator::make($data, [
                'nip'        => ['required', 'string', 'max:32'],
                'nama'       => ['required', 'string', 'max:255'],
                'email'      => ['nullable', 'email', 'max:255'],
                'peran'      => ['required', 'in:'.implode(',', $this->peranValid)],
                'kata_sandi' => ['nullable', 'string', 'min:8'],
            ], [
                'peran.in' => 'Peran harus salah satu: '.implode(', ', $this->peranValid).'.',
            ]);

            if ($validator->fails()) {
                $this->hasil->gagal[] = [
                    'baris' => $nomorBaris,
                    'pesan' => collect($validator->errors()->all())->implode('; '),
                ];

                continue;
            }

            $eksisting = Pengguna::where('nip', $data['nip'])->first();

            $atribut = [
                'nama'  => $data['nama'],
                'email' => $data['email'] ?: null,
                'peran' => $data['peran'],
            ];

            // Set kata sandi: saat membuat baru wajib ada (pakai default bila
            // kosong); saat update hanya bila diisi.
            if ($data['kata_sandi']) {
                $atribut['kata_sandi'] = $data['kata_sandi'];
            } elseif (! $eksisting) {
                $atribut['kata_sandi'] = self::KATA_SANDI_DEFAULT;
            }

            Pengguna::updateOrCreate(['nip' => $data['nip']], $atribut);

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
            'nip'        => trim((string) ($cari(['nip', 'nidn', 'nomor_induk']) ?? '')),
            'nama'       => trim((string) ($cari(['nama', 'nama_lengkap']) ?? '')),
            'email'      => ($e = trim((string) ($cari(['email', 'surel']) ?? ''))) !== '' ? $e : null,
            'peran'      => strtolower(trim((string) ($cari(['peran', 'role']) ?? ''))),
            'kata_sandi' => ($s = trim((string) ($cari(['kata_sandi', 'password', 'sandi']) ?? ''))) !== '' ? $s : null,
        ];
    }
}
