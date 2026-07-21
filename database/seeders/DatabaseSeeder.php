<?php

namespace Database\Seeders;

use App\Models\BeasiswaKategori;
use App\Models\BeasiswaPenerima;
use App\Models\KegiatanPromosi;
use App\Models\KegiatanKemahasiswaan;
use App\Models\KknDpl;
use App\Models\KknKelompok;
use App\Models\KknLokasi;
use App\Models\KknPeserta;
use App\Models\KlasterisasiKategori;
use App\Models\PengabdianHibah;
use App\Models\Mahasiswa;
use App\Models\NilaiIpkSemester;
use App\Models\Pengguna;
use App\Models\Prestasi;
use App\Models\ProgramStudi;
use App\Models\TracerStudy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Mengisi data awal:
     *  - 5 akun pengguna demo (satu per peran: admin, WD III, Staf WD III,
     *    Kaprodi, Staf Prodi).
     *  - 4 program studi FT UNSUR.
     *  - 30 mahasiswa (>=5 per prodi) dengan riwayat IPK 4-6 semester.
     */
    public function run(): void
    {
        $this->seedPengguna();
        $prodi = $this->seedProgramStudi();
        $this->seedMahasiswaDanIpk($prodi);

        // Data contoh modul pendukung (SIMULASI untuk demo, bukan data riil).
        $this->seedPrestasi();
        $this->seedTracer($prodi);
        $this->seedPromosi();
        $this->seedBeasiswa();
        $this->seedKkn();
        $this->seedSkkm();
        $this->seedKategoriKlaster();
    }

    /**
     * Katalog default "Kategori Klaster" — nama label + rekomendasi pembinaan
     * yang dipetakan ke hasil klaster menurut peringkat skor komposit
     * (urutan 1 = komposit tertinggi). Dapat diubah pimpinan lewat CRUD.
     */
    protected function seedKategoriKlaster(): void
    {
        $definisi = [
            [
                'nama'        => 'Berprestasi',
                'urutan'      => 1,
                'warna'      => 'cluster-1',
                'deskripsi'   => 'Skor komposit tertinggi: akademik kuat dan/atau aktivitas non-akademik menonjol.',
                'rekomendasi' => 'Pertahankan capaian; dorong kompetisi tingkat nasional/internasional, '
                    .'prioritaskan untuk beasiswa prestasi, dan libatkan sebagai mentor bagi klaster lain.',
            ],
            [
                'nama'        => 'Menengah',
                'urutan'      => 2,
                'warna'      => 'cluster-2',
                'deskripsi'   => 'Skor komposit menengah; potensi berkembang pada salah satu dimensi.',
                'rekomendasi' => 'Dorong keterlibatan kegiatan/organisasi dan keikutsertaan lomba agar '
                    .'profil non-akademik meningkat; berikan bimbingan akademik terarah.',
            ],
            [
                'nama'        => 'Perlu Bimbingan',
                'urutan'      => 3,
                'warna'      => 'cluster-3',
                'deskripsi'   => 'Skor komposit terendah: akademik dan aktivitas non-akademik sama-sama perlu perhatian.',
                'rekomendasi' => 'Pendampingan akademik intensif (perwalian/remedial), dorong keikutsertaan '
                    .'kegiatan dasar, dan pantau perkembangan IPK tiap semester.',
            ],
        ];

        foreach ($definisi as $isi) {
            KlasterisasiKategori::create([...$isi, 'aktif' => true]);
        }
    }

    /**
     * Lima akun pengguna demo — satu untuk tiap peran.
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
            'peran'      => 'staf_wd3',
        ]);

        Pengguna::factory()->create([
            'nip'        => '197805102003121003',
            'nama'       => 'Ir. Dedi Kurniawan, M.Kom.',
            'email'      => 'dedi@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran'      => 'kaprodi',
        ]);

        Pengguna::factory()->create([
            'nip'        => '199103252015042004',
            'nama'       => 'Rina Marlina, S.T.',
            'email'      => 'rina@ft.unsur.ac.id',
            'kata_sandi' => Hash::make('rahasia123'),
            'peran'      => 'staf_prodi',
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

    /**
     * Data contoh prestasi (1-2 per ~15 mahasiswa terpilih).
     * SIMULASI untuk keperluan demo — bukan data riil.
     */
    protected function seedPrestasi(): void
    {
        $terpilih = Mahasiswa::query()->inRandomOrder()->take(15)->get();

        foreach ($terpilih as $mahasiswa) {
            Prestasi::factory()
                ->count(fake()->numberBetween(1, 2))
                ->create(['mahasiswa_id' => $mahasiswa->id]);
        }
    }

    /**
     * Data contoh tracer study. Membuat 8 mahasiswa "alumni" (status lulus)
     * agar tidak mengganggu kohort klasterisasi (yang memakai mahasiswa aktif),
     * lalu mengisi tracer untuk masing-masing. SIMULASI untuk demo.
     *
     * @param  array<string, ProgramStudi>  $prodi
     */
    protected function seedTracer(array $prodi): void
    {
        $daftarProdi = array_values($prodi);

        for ($i = 0; $i < 8; $i++) {
            $alumni = Mahasiswa::factory()
                ->untukProdi($daftarProdi[$i % count($daftarProdi)])
                ->create([
                    'status'         => 'lulus',
                    'semester_aktif' => 8,
                ]);

            TracerStudy::factory()->create([
                'mahasiswa_id' => $alumni->id,
                'tahun_lulus'  => (int) $alumni->angkatan + 4,
            ]);
        }
    }

    /**
     * Data contoh kegiatan promosi/PMB. SIMULASI untuk demo.
     */
    protected function seedPromosi(): void
    {
        KegiatanPromosi::factory()->count(10)->create();
    }

    /**
     * Data contoh modul Beasiswa: 3 kategori realistis + penerima untuk
     * sejumlah mahasiswa aktif terpilih. SIMULASI untuk demo, bukan data riil.
     */
    protected function seedBeasiswa(): void
    {
        $definisiKategori = [
            ['kode' => 'KIP', 'nama' => 'Beasiswa KIP Kuliah', 'jenis_bantuan' => 'total', 'sumber_dana' => 'kemendikti'],
            ['kode' => 'UKTFT', 'nama' => 'Subsidi UKT Fakultas Teknik', 'jenis_bantuan' => 'ukt', 'sumber_dana' => 'ftunsur'],
            ['kode' => 'LLDIKTI4', 'nama' => 'Beasiswa LLDIKTI Wilayah IV', 'jenis_bantuan' => 'total', 'sumber_dana' => 'lldikti'],
        ];

        $kategori = [];
        foreach ($definisiKategori as $isi) {
            $kategori[] = BeasiswaKategori::create([...$isi, 'aktif' => true]);
        }

        // Penerima untuk 10 mahasiswa aktif terpilih.
        $terpilih = Mahasiswa::query()->where('status', 'aktif')->inRandomOrder()->take(10)->get();

        foreach ($terpilih as $i => $mahasiswa) {
            BeasiswaPenerima::factory()->create([
                'mahasiswa_id'         => $mahasiswa->id,
                'beasiswa_kategori_id' => $kategori[$i % count($kategori)]->id,
            ]);
        }
    }

    /**
     * Data contoh modul KKN: beberapa lokasi + DPL, lalu 3 kelompok yang
     * masing-masing diisi peserta dari mahasiswa aktif. SIMULASI untuk demo.
     */
    protected function seedKkn(): void
    {
        $lokasi = KknLokasi::factory()->count(4)->create();
        $dpl = KknDpl::factory()->count(4)->create();

        // Ambil mahasiswa aktif untuk dijadikan peserta (5 per kelompok).
        $mahasiswaAktif = Mahasiswa::query()->where('status', 'aktif')->inRandomOrder()->take(15)->get();
        $potongan = $mahasiswaAktif->chunk(5)->values();

        foreach ($potongan as $i => $anggota) {
            $kelompok = KknKelompok::factory()->create([
                'nama_kelompok'  => 'Kelompok '.($i + 1),
                'kkn_lokasi_id'  => $lokasi[$i % $lokasi->count()]->id,
                'kkn_dpl_id'     => $dpl[$i % $dpl->count()]->id,
                'tahun_akademik' => '2025/2026',
                'status'         => 'berjalan',
            ]);

            foreach ($anggota->values() as $j => $mahasiswa) {
                KknPeserta::factory()->create([
                    'kkn_kelompok_id' => $kelompok->id,
                    'mahasiswa_id'    => $mahasiswa->id,
                    'jabatan'         => $j === 0 ? 'ketua' : ($j === 1 ? 'sekretaris' : ($j === 2 ? 'bendahara' : 'anggota')),
                    'status'          => 'aktif',
                    'nilai_akhir'     => null,
                    'nilai_huruf'     => null,
                ]);
            }
        }
    }

    /**
     * Data contoh non-akademik SKKM: kegiatan/organisasi (F6) & pengabdian/
     * hibah (F7) untuk sebagian mahasiswa aktif. Poin dihitung dari rubrik.
     * SIMULASI untuk demo, bukan data riil.
     */
    protected function seedSkkm(): void
    {
        $terpilih = Mahasiswa::query()->where('status', 'aktif')->inRandomOrder()->take(18)->get();

        foreach ($terpilih as $i => $mahasiswa) {
            KegiatanKemahasiswaan::factory()
                ->count(fake()->numberBetween(1, 3))
                ->create(['mahasiswa_id' => $mahasiswa->id]);

            // Tidak semua mahasiswa punya pengabdian/hibah.
            if ($i % 2 === 0) {
                PengabdianHibah::factory()
                    ->count(fake()->numberBetween(1, 2))
                    ->create(['mahasiswa_id' => $mahasiswa->id]);
            }
        }
    }
}
