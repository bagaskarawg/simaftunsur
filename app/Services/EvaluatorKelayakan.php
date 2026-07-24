<?php

namespace App\Services;

use App\Enums\BidangKriteria;
use App\Enums\OperatorKriteria;
use App\Models\KlasterisasiEksekusi;
use App\Models\Mahasiswa;
use App\Models\Program;
use App\Models\ProgramSyarat;
use App\Support\HasilKelayakan;
use App\Support\HasilKriteria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Mengevaluasi kelayakan mahasiswa terhadap persyaratan sebuah program.
 *
 * Evaluasi bersifat BOOLEAN MURNI: mahasiswa layak bila SELURUH syarat wajib
 * terpenuhi (konjungsi/AND). Tiap kriteria dievaluasi INDEPENDEN — tidak ada
 * pembobotan, tidak ada skor agregat, tidak ada persentase kecocokan, dan tidak
 * ada aturan berantai (rule chaining). Ini setara query WHERE bertumpuk, bukan
 * SPK/Profile Matching.
 *
 * Pembagian query vs PHP (bahan BAB IV):
 *   - Field administratif (status/prodi/angkatan/semester) bersifat query-able
 *     (kolom nyata) → dapat dipersempit lewat WHERE via kandidatQuery().
 *   - Field turunan (IPK/tren/konsistensi/skor SKKM/label klaster) dievaluasi
 *     di PHP memakai method Model Mahasiswa yang sudah ada.
 * Evaluasi boolean final SELALU dilakukan di PHP agar tampilan audit (yang
 * menampilkan mahasiswa belum memenuhi) tetap konsisten.
 */
class EvaluatorKelayakan
{
    /** Peringkat tingkat prestasi untuk kriteria "jumlah prestasi min. tingkat". */
    private const RANK_TINGKAT = [
        'lokal' => 1,
        'regional' => 2,
        'nasional' => 3,
        'internasional' => 4,
    ];

    /** Cache peta [mahasiswa_id => label klaster] dari eksekusi terbaru. */
    private ?Collection $petaLabelKlaster = null;

    /**
     * Evaluasi seluruh mahasiswa terhadap sebuah program.
     *
     * @param  Collection<int, Mahasiswa>|null  $mahasiswa  Bila null, diambil dari
     *                                                      kandidatQuery() (dengan optimasi WHERE untuk field query-able).
     * @return Collection<int, HasilKelayakan>
     */
    public function evaluateProgram(Program $program, ?Collection $mahasiswa = null): Collection
    {
        $program->loadMissing('syarat');
        $mahasiswa ??= $this->kandidatQuery($program)->get();

        return $mahasiswa->map(function (Mahasiswa $m) use ($program) {
            $kriteria = $this->evaluateStudent($program, $m);
            $layak = collect($kriteria)
                ->filter(fn (HasilKriteria $k) => $k->wajib)
                ->every(fn (HasilKriteria $k) => $k->lolos);

            return new HasilKelayakan($m, $layak, $kriteria);
        })->values();
    }

    /**
     * Evaluasi status tiap kriteria program untuk satu mahasiswa.
     *
     * @return array<int, HasilKriteria>
     */
    public function evaluateStudent(Program $program, Mahasiswa $mahasiswa): array
    {
        return $program->syarat
            ->map(fn (ProgramSyarat $syarat) => $this->evaluasiSatuKriteria($syarat, $mahasiswa))
            ->all();
    }

    /**
     * Query builder kandidat dengan optimasi WHERE untuk syarat WAJIB yang
     * query-able. Field turunan tetap dievaluasi di PHP setelah pengambilan.
     */
    public function kandidatQuery(Program $program): Builder
    {
        $query = Mahasiswa::query()->with([
            'programStudi', 'nilaiIpkSemester', 'prestasi',
            'kegiatanKemahasiswaan', 'pengabdianHibah',
        ]);

        foreach ($program->syaratWajib as $syarat) {
            $bidang = $syarat->bidangEnum();
            $operator = $syarat->operatorEnum();
            if (! $bidang?->bisaQuery() || ! $operator) {
                continue;
            }
            $this->terapkanKeQuery($query, $bidang, $operator, $syarat->nilaiTerdecode());
        }

        return $query;
    }

    /** Evaluasi satu kriteria menjadi HasilKriteria (boolean). */
    private function evaluasiSatuKriteria(ProgramSyarat $syarat, Mahasiswa $mahasiswa): HasilKriteria
    {
        $bidang = $syarat->bidangEnum();
        $operator = $syarat->operatorEnum();

        // Kriteria dengan konfigurasi tidak dikenal dianggap tidak lolos (jujur).
        if (! $bidang || ! $operator) {
            return new HasilKriteria(
                $syarat->bidang, $syarat->label, $syarat->wajib,
                null, $syarat->nilai, false, 'konfigurasi kriteria tidak dikenal',
            );
        }

        // Kriteria khusus: jumlah prestasi minimal pada suatu tingkat.
        if ($bidang === BidangKriteria::JumlahPrestasiMinTingkat) {
            return $this->evaluasiJumlahPrestasi($syarat, $mahasiswa);
        }

        $aktual = $this->nilaiAktual($bidang, $mahasiswa);
        $ambang = $syarat->nilaiTerdecode();

        // Data belum tersedia (mis. belum ada IPK / belum diklaster).
        if ($aktual === null) {
            $keterangan = $bidang === BidangKriteria::LabelKlaster
                ? 'belum diklaster'
                : 'data belum tersedia';

            return new HasilKriteria(
                $syarat->bidang, $syarat->label, $syarat->wajib,
                null, $ambang, false, $keterangan,
            );
        }

        return new HasilKriteria(
            $syarat->bidang, $syarat->label, $syarat->wajib,
            $aktual, $ambang, $operator->bandingkan($aktual, $ambang),
        );
    }

    /**
     * Kriteria "minimal N prestasi pada tingkat tertentu" (nilai JSON
     * {"tingkat":"nasional","min_jumlah":1}). Menghitung cacah prestasi pada
     * tingkat >= target lalu membandingkan dengan ambang (>=). Cacahan ini
     * data mentah, bukan skor komposit.
     */
    private function evaluasiJumlahPrestasi(ProgramSyarat $syarat, Mahasiswa $mahasiswa): HasilKriteria
    {
        $konfig = $syarat->nilaiTerdecode();
        $tingkat = is_array($konfig) ? ($konfig['tingkat'] ?? null) : null;
        $minJumlah = (int) (is_array($konfig) ? ($konfig['min_jumlah'] ?? 1) : 1);
        $rankTarget = self::RANK_TINGKAT[$tingkat] ?? 0;

        $jumlah = $mahasiswa->prestasi
            ->filter(fn ($p) => (self::RANK_TINGKAT[$p->tingkat] ?? 0) >= $rankTarget)
            ->count();

        $labelTingkat = ucfirst((string) $tingkat);

        return new HasilKriteria(
            $syarat->bidang,
            $syarat->label,
            $syarat->wajib,
            "$jumlah prestasi ≥ $labelTingkat",
            "min. $minJumlah",
            $jumlah >= $minJumlah,
        );
    }

    /** Ambil nilai aktual mahasiswa untuk field non-khusus. */
    private function nilaiAktual(BidangKriteria $bidang, Mahasiswa $mahasiswa): mixed
    {
        return match ($bidang) {
            BidangKriteria::IpkRataRata => $mahasiswa->ipkRataRata(),
            BidangKriteria::IpkTerakhir => $mahasiswa->ipkTerakhir(),
            BidangKriteria::Tren => $mahasiswa->tren(),
            BidangKriteria::Konsistensi => $mahasiswa->konsistensi(),
            BidangKriteria::SkorPrestasi => $mahasiswa->skorPrestasi(),
            BidangKriteria::SkorKegiatan => $mahasiswa->skorKegiatan(),
            BidangKriteria::SkorPengabdian => $mahasiswa->skorPengabdian(),
            BidangKriteria::Status => $mahasiswa->status,
            BidangKriteria::ProgramStudi => $mahasiswa->programStudi?->kode,
            BidangKriteria::Angkatan => $mahasiswa->angkatan,
            BidangKriteria::SemesterAktif => $mahasiswa->semester_aktif,
            BidangKriteria::LabelKlaster => $this->labelKlaster($mahasiswa->id),
            default => null,
        };
    }

    /** Label klaster mahasiswa dari eksekusi K-Means TERBARU (atau null). */
    private function labelKlaster(int $mahasiswaId): ?string
    {
        if ($this->petaLabelKlaster === null) {
            $eksekusi = KlasterisasiEksekusi::latest()->first();
            $this->petaLabelKlaster = $eksekusi
                ? $eksekusi->anggota()->with('klaster:id,label_deskriptif')->get()
                    ->mapWithKeys(fn ($a) => [$a->mahasiswa_id => $a->klaster?->label_deskriptif])
                : collect();
        }

        return $this->petaLabelKlaster->get($mahasiswaId);
    }

    /** Terapkan satu syarat query-able ke query builder. */
    private function terapkanKeQuery(Builder $query, BidangKriteria $bidang, OperatorKriteria $operator, mixed $nilai): void
    {
        if ($bidang === BidangKriteria::ProgramStudi) {
            $query->whereHas('programStudi', function (Builder $q) use ($operator, $nilai) {
                $operator === OperatorKriteria::In
                    ? $q->whereIn('kode', (array) $nilai)
                    : $q->where('kode', $this->simbolSql($operator), $nilai);
            });

            return;
        }

        $kolom = match ($bidang) {
            BidangKriteria::Status => 'status',
            BidangKriteria::Angkatan => 'angkatan',
            BidangKriteria::SemesterAktif => 'semester_aktif',
            default => null,
        };
        if ($kolom === null) {
            return;
        }

        $operator === OperatorKriteria::In
            ? $query->whereIn($kolom, (array) $nilai)
            : $query->where($kolom, $this->simbolSql($operator), $nilai);
    }

    /** Terjemahkan operator ke simbol SQL untuk klausa WHERE. */
    private function simbolSql(OperatorKriteria $operator): string
    {
        return match ($operator) {
            OperatorKriteria::Gte => '>=',
            OperatorKriteria::Lte => '<=',
            OperatorKriteria::Gt => '>',
            OperatorKriteria::Lt => '<',
            default => '=',
        };
    }
}
