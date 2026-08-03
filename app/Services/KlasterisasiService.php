<?php

namespace App\Services;

use App\Models\KlasterisasiAnggota;
use App\Models\KlasterisasiEksekusi;
use App\Models\KlasterisasiKategori;
use App\Models\KlasterisasiKlaster;
use App\Models\Mahasiswa;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

/**
 * Penghubung Laravel ↔ service Python (FastAPI) untuk klasterisasi K-Means.
 *
 * Tanggung jawab:
 *  1. Menyaring mahasiswa yang layak diklaster.
 *  2. Memetakan tiap mahasiswa menjadi vektor fitur (memakai helper Model
 *     Mahasiswa: ipkRataRata/ipkTerakhir/tren/konsistensi).
 *  3. Mengirim ke service Python, lalu menyimpan hasil + metrik ke basis data.
 */
class KlasterisasiService
{
    /**
     * Jumlah minimum catatan IPK agar fitur tren & konsistensi bermakna,
     * sekaligus selaras dengan Batasan Masalah (mahasiswa ≥ 3 semester).
     */
    public const MIN_CATATAN_IPK = 3;

    /**
     * Ambang volume ideal sesuai Batasan Masalah: minimum 100 mahasiswa aktif
     * yang telah menempuh ≥ 3 semester. Di bawah ini klaster tetap bisa
     * dijalankan, tetapi hasil ditandai indikatif (lihat service Python).
     */
    public const VOLUME_IDEAL_MIN = 100;

    /**
     * Hitung kesiapan data untuk klasterisasi — dipakai sebagai validasi
     * volume di antarmuka (halaman klasterisasi & impor IPK).
     *
     * @return array{total:int, aktif:int, layak:int, aktif_kurang_ipk:int, ambang:int, min_catatan:int, kurang:int, persen:int, siap:bool, cukup_untuk_jalan:bool}
     */
    public function kesiapan(): array
    {
        $total = Mahasiswa::count();
        $aktif = Mahasiswa::where('status', 'aktif')->count();
        $layak = Mahasiswa::where('status', 'aktif')
            ->has('nilaiIpkSemester', '>=', self::MIN_CATATAN_IPK)
            ->count();

        $persen = (int) min(100, round($layak / self::VOLUME_IDEAL_MIN * 100));

        return [
            'total' => $total,
            'aktif' => $aktif,
            'layak' => $layak,
            'aktif_kurang_ipk' => max(0, $aktif - $layak),
            'ambang' => self::VOLUME_IDEAL_MIN,
            'min_catatan' => self::MIN_CATATAN_IPK,
            'kurang' => max(0, self::VOLUME_IDEAL_MIN - $layak),
            'persen' => $persen,
            'siap' => $layak >= self::VOLUME_IDEAL_MIN,
            'cukup_untuk_jalan' => $layak >= 3,
        ];
    }

    /**
     * Cek kesehatan service Python sebelum mengirim data.
     */
    public function sehat(): bool
    {
        try {
            return Http::timeout(5)
                ->withHeaders($this->header())
                ->get($this->url('/sehat'))
                ->successful();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Jalankan klasterisasi ujung-ke-ujung dan simpan hasilnya.
     *
     * @param  array{fitur?: list<string>|null, k?: int|null, k_min?: int, k_max?: int, skema_penskalaan?: string}  $opsi
     *
     * @throws RuntimeException bila data tak cukup atau service menolak/gagal.
     */
    public function jalankan(array $opsi = []): KlasterisasiEksekusi
    {
        $mahasiswa = $this->ambilMahasiswaLayak();

        if ($mahasiswa->count() < 3) {
            throw new RuntimeException(
                'Data mahasiswa yang layak diklaster kurang dari 3 (mahasiswa aktif '
                .'dengan minimal '.self::MIN_CATATAN_IPK.' catatan IPK). '
                .'Tambahkan data IPK terlebih dahulu.'
            );
        }

        $muatan = [
            'data' => $mahasiswa->map(fn (Mahasiswa $m) => $this->petakanFitur($m))->values()->all(),
            'fitur' => $opsi['fitur'] ?? null,
            'k' => $opsi['k'] ?? null,
            'k_min' => $opsi['k_min'] ?? 2,
            'k_max' => $opsi['k_max'] ?? 8,
            'skema_penskalaan' => $opsi['skema_penskalaan'] ?? 'standard',
            'konfigurasi_label' => $this->konfigurasiLabel(),
        ];

        try {
            $respons = Http::timeout((int) config('services.ml.timeout', 60))
                ->withHeaders($this->header())
                ->acceptJson()
                ->asJson()
                ->post($this->url('/klasterisasi'), $muatan);
        } catch (Throwable $e) {
            throw new RuntimeException(
                'Tidak dapat terhubung ke service klasterisasi. Pastikan service '
                .'Python berjalan (uvicorn api:app --port 8001). Detail: '.$e->getMessage()
            );
        }

        // Galat domain dari service (mis. data tak valid) → pesan ramah.
        if ($respons->status() === 422) {
            throw new RuntimeException(
                'Service menolak permintaan: '.($respons->json('detail') ?? 'data tidak valid.')
            );
        }

        if ($respons->failed()) {
            throw new RuntimeException(
                'Service klasterisasi mengembalikan galat (HTTP '.$respons->status().').'
            );
        }

        return $this->simpan($respons->json(), $mahasiswa);
    }

    /**
     * Mahasiswa yang layak diklaster: berstatus aktif dan memiliki minimal
     * MIN_CATATAN_IPK catatan IPK. Relasi dimuat awal untuk efisiensi.
     *
     * @return Collection<int, Mahasiswa>
     */
    public function ambilMahasiswaLayak(): Collection
    {
        return Mahasiswa::query()
            ->where('status', 'aktif')
            ->has('nilaiIpkSemester', '>=', self::MIN_CATATAN_IPK)
            ->with(['programStudi', 'nilaiIpkSemester', 'prestasi', 'kegiatanKemahasiswaan', 'pengabdianHibah'])
            ->get();
    }

    /**
     * Petakan satu mahasiswa menjadi vektor fitur untuk klasterisasi.
     *
     * @return array<string, mixed>
     */
    protected function petakanFitur(Mahasiswa $mahasiswa): array
    {
        return [
            'id' => $mahasiswa->id,
            'npm' => $mahasiswa->npm,
            'nama' => $mahasiswa->nama,
            ...$this->snapshotFitur($mahasiswa),
        ];
    }

    /**
     * Nilai fitur mahasiswa (satuan asli) yang menjadi dasar perhitungan.
     * Dibekukan ke `klasterisasi_anggota.fitur_nilai` agar penempatan klaster
     * dapat ditelusuri & dipertanggungjawabkan walau data IPK berubah kemudian.
     *
     * @return array<string, mixed>
     */
    protected function snapshotFitur(Mahasiswa $mahasiswa): array
    {
        return [
            // Blok akademik (F1–F4)
            'ipk_rata_rata' => $mahasiswa->ipkRataRata(),
            'ipk_terakhir' => $mahasiswa->ipkTerakhir() ?? 0.0,
            'tren' => $mahasiswa->tren(),
            'konsistensi' => $mahasiswa->konsistensi(),
            // Blok non-akademik SKKM (F5–F7)
            'skor_prestasi' => $mahasiswa->skorPrestasi(),
            'skor_kegiatan' => $mahasiswa->skorKegiatan(),
            'skor_pengabdian' => $mahasiswa->skorPengabdian(),
            // Konteks (bukan bagian 7 fitur inti, disertakan untuk analisis)
            'semester_aktif' => $mahasiswa->semester_aktif,
            'program_studi' => $mahasiswa->programStudi?->kode,
        ];
    }

    /**
     * Susun konfigurasi pelabelan yang dikirim ke service Python: bobot & arah
     * fitur dari config, katalog nama dari master KlasterisasiKategori. Bila
     * katalog kosong, service memakai katalog default bawaannya.
     *
     * @return array<string, mixed>
     */
    protected function konfigurasiLabel(): array
    {
        $konfig = (array) config('klasterisasi.label', []);

        $katalog = KlasterisasiKategori::katalogAktif();
        if ($katalog !== []) {
            $konfig['katalog'] = $katalog;
        }

        return $konfig;
    }

    /**
     * Simpan tanggapan service ke tabel eksekusi + anggota dalam satu transaksi.
     *
     * @param  array<string, mixed>  $hasil
     * @param  Collection<int, Mahasiswa>  $mahasiswa
     */
    protected function simpan(array $hasil, Collection $mahasiswa): KlasterisasiEksekusi
    {
        // Rekomendasi pembinaan per label (dari master Kategori Klaster).
        $rekomendasiPerNama = KlasterisasiKategori::query()
            ->pluck('rekomendasi', 'nama');

        // Peta mahasiswa per-id untuk snapshot fitur; hanya id di set yang
        // dikirim yang disimpan (pengaman bila service kembalikan id asing).
        $mahasiswaPerId = $mahasiswa->keyBy('id');

        return DB::transaction(function () use ($hasil, $mahasiswaPerId, $rekomendasiPerNama) {
            $eksekusi = KlasterisasiEksekusi::create([
                'k_terpilih' => $hasil['k_terpilih'],
                'metode_pemilihan_k' => $hasil['metode_pemilihan_k'],
                'fitur_dipakai' => $hasil['fitur_dipakai'],
                'skema_penskalaan' => $hasil['skema_penskalaan'],
                'random_state' => $hasil['random_state'] ?? null,
                'versi_algoritma' => $hasil['versi_algoritma'] ?? null,
                'kriteria_data' => $hasil['kriteria_data']
                    ?? ('mahasiswa aktif dengan >='.self::MIN_CATATAN_IPK.' catatan IPK'),
                'jumlah_data' => $hasil['jumlah_data'],
                'silhouette' => $hasil['metrik']['silhouette'] ?? null,
                'davies_bouldin' => $hasil['metrik']['davies_bouldin'] ?? null,
                'inertia' => $hasil['metrik']['inertia'] ?? null,
                'evaluasi_k' => $hasil['evaluasi_k'],
                'profil_klaster' => $hasil['profil_klaster'],
                // Validasi lanjutan (pelengkap) — null bila service tak mengirim.
                'stabilitas' => $hasil['stabilitas'] ?? null,
                'stabilitas_rata' => $hasil['stabilitas']['rata_rata'] ?? null,
                'uji_beda' => $hasil['uji_beda'] ?? null,
                'peringatan' => $hasil['peringatan'] ?? [],
                'dijalankan_oleh' => auth()->id(),
            ]);

            // Simpan profil tiap klaster (ternormalisasi) & petakan cluster→id.
            $petaKlaster = [];
            foreach ($hasil['profil_klaster'] ?? [] as $profil) {
                $label = $profil['label_deskriptif'] ?? null;
                $klaster = KlasterisasiKlaster::create([
                    'eksekusi_id' => $eksekusi->id,
                    'cluster' => $profil['cluster'],
                    'label_deskriptif' => $label,
                    'jumlah_anggota' => $profil['jumlah'] ?? $profil['jumlah_anggota'] ?? 0,
                    'centroid' => $profil['centroid'] ?? [],
                    'centroid_terskala' => $profil['centroid_terskala'] ?? null,
                    // Skor komposit multi-fitur = dasar penentuan label (transparansi).
                    'skor_akademik' => $profil['skor_akademik'] ?? null,
                    'skor_non_akademik' => $profil['skor_non_akademik'] ?? null,
                    'skor_komposit' => $profil['skor_komposit'] ?? null,
                    'ringkasan_profil' => $profil['ringkasan_profil'] ?? null,
                    // Interpretasi = rekomendasi pembinaan dari master Kategori Klaster
                    // (dipetakan lewat nama label), fallback ke keluaran service.
                    'interpretasi' => $rekomendasiPerNama[$label] ?? ($profil['interpretasi'] ?? null),
                ]);
                $petaKlaster[$profil['cluster']] = $klaster->id;
            }

            // Anggota + snapshot fitur. Bulk insert melewati cast, jadi kolom
            // JSON di-encode manual.
            $baris = [];
            foreach ($hasil['hasil'] as $titik) {
                $mahasiswaTitik = $mahasiswaPerId->get($titik['id']);
                if (! $mahasiswaTitik) {
                    continue;
                }
                $baris[] = [
                    'eksekusi_id' => $eksekusi->id,
                    'klaster_id' => $petaKlaster[$titik['cluster']] ?? null,
                    'mahasiswa_id' => $titik['id'],
                    'cluster' => $titik['cluster'],
                    'fitur_nilai' => json_encode($this->snapshotFitur($mahasiswaTitik)),
                    'fitur_terskala' => isset($titik['fitur_terskala']) ? json_encode($titik['fitur_terskala']) : null,
                    'jarak_ke_centroid' => $titik['jarak_ke_centroid'] ?? null,
                    'pca_x' => $titik['pca_x'],
                    'pca_y' => $titik['pca_y'],
                ];
            }

            KlasterisasiAnggota::insert($baris);

            return $eksekusi;
        });
    }

    /**
     * Bentuk URL penuh ke endpoint service ML.
     */
    protected function url(string $path): string
    {
        return rtrim((string) config('services.ml.base_url'), '/').$path;
    }

    /**
     * Header autentikasi untuk service ML. Mengirim X-API-Key hanya bila
     * kunci dikonfigurasi (di server); saat lokal tanpa kunci → header kosong.
     *
     * @return array<string, string>
     */
    protected function header(): array
    {
        $kunci = (string) config('services.ml.api_key');

        return $kunci !== '' ? ['X-API-Key' => $kunci] : [];
    }
}
