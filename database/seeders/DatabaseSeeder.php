<?php

namespace Database\Seeders;

use App\Models\BeasiswaKategori;
use App\Models\BeasiswaPenerima;
use App\Models\KegiatanKemahasiswaan;
use App\Models\KegiatanPromosi;
use App\Models\KknDpl;
use App\Models\KknKelompok;
use App\Models\KknLokasi;
use App\Models\KknPeserta;
use App\Models\KlasterisasiKategori;
use App\Models\Mahasiswa;
use App\Models\PengabdianHibah;
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
     *  - Roster NYATA 1.225 mahasiswa Teknik dari berkas PMB via
     *    MahasiswaTeknikSeeder (tanpa data mahasiswa dummy).
     *  - Profil klasterisasi DEMO (IPK F1–F4 + non-akademik F5–F7) via
     *    ProfilKlasterDemoSeeder, sebaran 2D akademik × non-akademik.
     */
    public function run(): void
    {
        // Peran & penugasan izin (RBAC di DB) — sebelum pengguna agar peran ada.
        $this->call(PeranSeeder::class);

        $this->seedPengguna();
        $prodi = $this->seedProgramStudi();

        // Roster mahasiswa Teknik NYATA (1.225 mhs) dari ekstraksi berkas PMB
        // 2019–2023. Ini satu-satunya sumber mahasiswa (tanpa data dummy).
        $this->call(MahasiswaTeknikSeeder::class);

        // Profil klasterisasi DEMO/SIMULASI (berkas PMB tak memuat IPK/SKKM):
        // IPK per semester (F1–F4) + catatan prestasi/kegiatan/pengabdian
        // (F5–F7) dengan sebaran 2 dimensi akademik × non-akademik agar edge
        // case (mis. IPK tinggi tapi non-akademik rendah) benar-benar ada.
        $this->call(ProfilKlasterDemoSeeder::class);

        // Data contoh modul pendukung (SIMULASI untuk demo, bukan data riil).
        $this->seedPrestasi();
        $this->seedTracer($prodi);
        $this->seedPromosi();
        $this->seedBeasiswa();
        $this->seedKkn();
        $this->seedSkkm();
        $this->seedKategoriKlaster();

        // Program contoh untuk demo Penyaringan Kandidat.
        $this->call(ProgramSeeder::class);
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
                'nama' => 'Berprestasi',
                'urutan' => 1,
                'warna' => 'cluster-1',
                'deskripsi' => 'Skor komposit tertinggi: akademik kuat dan/atau aktivitas non-akademik menonjol.',
                'rekomendasi' => 'Pertahankan capaian; dorong kompetisi tingkat nasional/internasional, '
                    .'prioritaskan untuk beasiswa prestasi, dan libatkan sebagai mentor bagi klaster lain.',
            ],
            [
                'nama' => 'Menengah',
                'urutan' => 2,
                'warna' => 'cluster-2',
                'deskripsi' => 'Skor komposit menengah; potensi berkembang pada salah satu dimensi.',
                'rekomendasi' => 'Dorong keterlibatan kegiatan/organisasi dan keikutsertaan lomba agar '
                    .'profil non-akademik meningkat; berikan bimbingan akademik terarah.',
            ],
            [
                'nama' => 'Perlu Bimbingan',
                'urutan' => 3,
                'warna' => 'cluster-3',
                'deskripsi' => 'Skor komposit terendah: akademik dan aktivitas non-akademik sama-sama perlu perhatian.',
                'rekomendasi' => 'Pendampingan akademik intensif (perwalian/remedial), dorong keikutsertaan '
                    .'kegiatan dasar, dan pantau perkembangan IPK tiap semester.',
            ],
        ];

        // Idempoten: firstOrCreate berdasarkan `nama` label.
        foreach ($definisi as $isi) {
            KlasterisasiKategori::firstOrCreate(['nama' => $isi['nama']], [...$isi, 'aktif' => true]);
        }
    }

    /**
     * Lima akun pengguna demo — satu untuk tiap peran.
     *
     * Idempoten: firstOrCreate berdasarkan `nip`. Bila akun sudah ada, TIDAK
     * ditimpa (kata sandi yang mungkin sudah diganti admin tetap aman).
     */
    protected function seedPengguna(): void
    {
        $daftar = [
            ['nip' => 'admin', 'nama' => 'Administrator Sistem', 'email' => 'admin@ft.unsur.ac.id', 'peran' => 'admin'],
            ['nip' => '197003051998031001', 'nama' => 'Dr. Ir. Budi Santoso, M.T.', 'email' => 'budi.santoso@ft.unsur.ac.id', 'peran' => 'wd3'],
            ['nip' => '198506152012121002', 'nama' => 'Siti Nurhaliza, S.Kom.', 'email' => 'siti@ft.unsur.ac.id', 'peran' => 'staf_wd3'],
            ['nip' => '197805102003121003', 'nama' => 'Ir. Dedi Kurniawan, M.Kom.', 'email' => 'dedi@ft.unsur.ac.id', 'peran' => 'kaprodi'],
            ['nip' => '199103252015042004', 'nama' => 'Rina Marlina, S.T.', 'email' => 'rina@ft.unsur.ac.id', 'peran' => 'staf_prodi'],
        ];

        foreach ($daftar as $akun) {
            Pengguna::firstOrCreate(
                ['nip' => $akun['nip']],
                [
                    'nama' => $akun['nama'],
                    'email' => $akun['email'],
                    'kata_sandi' => Hash::make('rahasia123'),
                    'peran' => $akun['peran'],
                ],
            );
        }
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

        // Idempoten: firstOrCreate berdasarkan `kode` prodi.
        $prodi = [];
        foreach ($definisi as $isi) {
            $prodi[$isi['kode']] = ProgramStudi::firstOrCreate(
                ['kode' => $isi['kode']],
                ['nama' => $isi['nama'], 'jenjang' => 'S1'],
            );
        }

        return $prodi;
    }

    /**
     * Data contoh prestasi (1-2 per ~15 mahasiswa terpilih).
     * SIMULASI untuk keperluan demo — bukan data riil.
     */
    protected function seedPrestasi(): void
    {
        // Idempoten (sekali isi): lewati bila sudah pernah di-seed.
        if (Prestasi::query()->exists()) {
            return;
        }

        $terpilih = Mahasiswa::query()->inRandomOrder()->take(15)->get();

        foreach ($terpilih as $mahasiswa) {
            Prestasi::factory()
                ->count(fake()->numberBetween(1, 2))
                ->create(['mahasiswa_id' => $mahasiswa->id]);
        }
    }

    /**
     * Data contoh tracer study memakai mahasiswa NYATA berstatus lulus
     * (bukan alumni dummy) agar konsisten dengan prinsip "data real saja".
     * Isi tracer-nya tetap SIMULASI untuk demo.
     *
     * @param  array<string, ProgramStudi>  $prodi  (tak dipakai; dipertahankan
     *                                               agar tanda tangan run() stabil)
     */
    protected function seedTracer(array $prodi): void
    {
        // Idempoten (sekali isi): lewati bila sudah pernah di-seed.
        if (TracerStudy::query()->exists()) {
            return;
        }

        $alumni = Mahasiswa::query()
            ->where('status', 'lulus')
            ->inRandomOrder()
            ->take(8)
            ->get();

        foreach ($alumni as $mahasiswa) {
            TracerStudy::factory()->create([
                'mahasiswa_id' => $mahasiswa->id,
                'tahun_lulus' => (int) $mahasiswa->angkatan + 4,
            ]);
        }
    }

    /**
     * Data contoh kegiatan promosi/PMB. SIMULASI untuk demo.
     */
    protected function seedPromosi(): void
    {
        // Idempoten (sekali isi): lewati bila sudah pernah di-seed.
        if (KegiatanPromosi::query()->exists()) {
            return;
        }

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

        // Kategori bersifat master → idempoten via firstOrCreate (kode unik).
        $kategori = [];
        foreach ($definisiKategori as $isi) {
            $kategori[] = BeasiswaKategori::firstOrCreate(['kode' => $isi['kode']], [...$isi, 'aktif' => true]);
        }

        // Penerima = data demo (sekali isi): lewati bila sudah ada.
        if (BeasiswaPenerima::query()->exists()) {
            return;
        }

        // Penerima untuk 10 mahasiswa aktif terpilih.
        $terpilih = Mahasiswa::query()->where('status', 'aktif')->inRandomOrder()->take(10)->get();

        foreach ($terpilih as $i => $mahasiswa) {
            BeasiswaPenerima::factory()->create([
                'mahasiswa_id' => $mahasiswa->id,
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
        // Idempoten (sekali isi): lewati bila kelompok KKN sudah pernah dibuat.
        if (KknKelompok::query()->exists()) {
            return;
        }

        $lokasi = KknLokasi::factory()->count(4)->create();
        $dpl = KknDpl::factory()->count(4)->create();

        // Ambil mahasiswa aktif untuk dijadikan peserta (5 per kelompok).
        $mahasiswaAktif = Mahasiswa::query()->where('status', 'aktif')->inRandomOrder()->take(15)->get();
        $potongan = $mahasiswaAktif->chunk(5)->values();

        foreach ($potongan as $i => $anggota) {
            $kelompok = KknKelompok::factory()->create([
                'nama_kelompok' => 'Kelompok '.($i + 1),
                'kkn_lokasi_id' => $lokasi[$i % $lokasi->count()]->id,
                'kkn_dpl_id' => $dpl[$i % $dpl->count()]->id,
                'tahun_akademik' => '2025/2026',
                'status' => 'berjalan',
            ]);

            foreach ($anggota->values() as $j => $mahasiswa) {
                KknPeserta::factory()->create([
                    'kkn_kelompok_id' => $kelompok->id,
                    'mahasiswa_id' => $mahasiswa->id,
                    'jabatan' => $j === 0 ? 'ketua' : ($j === 1 ? 'sekretaris' : ($j === 2 ? 'bendahara' : 'anggota')),
                    'status' => 'aktif',
                    'nilai_akhir' => null,
                    'nilai_huruf' => null,
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
        // Idempoten (sekali isi): lewati bila sudah pernah di-seed.
        if (KegiatanKemahasiswaan::query()->exists()) {
            return;
        }

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
