<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\ProgramStudi;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal:
     *  - 3 akun pengguna demo (admin, WD III, staf).
     *  - 4 program studi FT UNSUR.
     *  - 30 mahasiswa (>=5 per prodi) dengan riwayat IPK 4-6 semester.
     */
    public function run(): void
    {
        $this->seedPengguna();
        $prodi = $this->seedProgramStudi();
        $this->seedMahasiswaDanIpk($prodi);
    }

    /**
     * Tiga akun pengguna demo untuk uji peran berbeda.
     */
    protected function seedPengguna(): void
    {
        Pengguna::factory()->create([
            'nip'        => 'admin',
            'nama'       => 'Administrator Sistem',
            'email'      => 'admin@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran'      => 'admin',
        ]);

        Pengguna::factory()->create([
            'nip'        => '197003051998031001',
            'nama'       => 'Dr. Ir. Budi Santoso, M.T.',
            'email'      => 'budi.santoso@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran'      => 'wd3',
        ]);

        Pengguna::factory()->create([
            'nip'        => '198506152012121002',
            'nama'       => 'Siti Nurhaliza, S.Kom.',
            'email'      => 'siti@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran'      => 'staf',
        ]);
    }

    /**
     * Empat program studi resmi FT UNSUR (seluruhnya jenjang S1).
     *
     * @return array<string, ProgramStudi>
     */
    protected function seedProgramStudi(): array
    {
        $definisi = [
            ['kode' => 'TIF', 'nama' => 'Teknik Informatika'],
            ['kode' => 'TSI', 'nama' => 'Teknik Sipil'],
            ['kode' => 'TMI', 'nama' => 'Teknik Mesin'],
            ['kode' => 'TID', 'nama' => 'Teknik Industri'],
        ];

        $prodi = [];
        foreach ($definisi as $isi) {
            $prodi[$isi['kode']] = ProgramStudi::create([
                ...$isi,
                'jenjang' => 'S1',
            ]);
        }

        return $prodi;
    }

    /**
     * Hasilkan 30 mahasiswa terdistribusi merata ke 4 prodi (>=5/ prodi)
     * lengkap dengan riwayat IPK semester 1 .. semester_aktif.
     *
     * @param  array<string, ProgramStudi>  $prodi
     */
    protected function seedMahasiswaDanIpk(array $prodi): void
    {
        $daftarProdi = array_values($prodi);
        $totalProdi  = count($daftarProdi);
        $jumlahMahasiswa = 30;

        for ($i = 0; $i < $jumlahMahasiswa; $i++) {
            // Distribusi round-robin agar tiap prodi pasti >= 5 mahasiswa
            // (30 / 4 = 7 atau 8 per prodi).
            $prodiTerpilih = $daftarProdi[$i % $totalProdi];

            $semesterAktif = fake()->numberBetween(3, 7);

            $mahasiswa = Mahasiswa::factory()
                ->untukProdi($prodiTerpilih)
                ->denganSemester($semesterAktif)
                ->create();

            $this->seedRiwayatIpk($mahasiswa, $semesterAktif);
        }
    }

    /**
     * Buat catatan IPK dari semester 1 sampai semester aktif mahasiswa,
     * dengan minimal 4 dan maksimal 6 catatan (mengikuti ketentuan task).
     */
    protected function seedRiwayatIpk(Mahasiswa $mahasiswa, int $semesterAktif): void
    {
        $jumlahCatatan = min($semesterAktif, fake()->numberBetween(4, 6));

        for ($semester = 1; $semester <= $jumlahCatatan; $semester++) {
            NilaiIpkSemester::factory()->create([
                'mahasiswa_id'          => $mahasiswa->id,
                'semester'              => $semester,
                'semester_ganjil_genap' => $semester % 2 === 1 ? 'ganjil' : 'genap',
            ]);
        }
    }
}
