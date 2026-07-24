<?php

namespace App\Enums;

/**
 * Daftar field yang dapat dijadikan kriteria persyaratan program.
 *
 * Seluruh field bersumber dari data yang SUDAH ADA di sistem (tidak ada data
 * baru yang dikarang). Metadata tiap field — tipe, operator valid, kategori,
 * dan apakah dapat diterjemahkan ke klausa WHERE (query-able) — dipakai oleh
 * Form Request (validasi kombinasi field–operator) dan EvaluatorKelayakan.
 *
 * Nilai case sengaja memakai kunci yang selaras dengan snapshot fitur yang
 * sudah dipakai modul klasterisasi (ipk_rata_rata, skor_prestasi, dll).
 */
enum BidangKriteria: string
{
    // Akademik (dari method Model Mahasiswa: ipkRataRata/ipkTerakhir/tren/konsistensi).
    case IpkRataRata = 'ipk_rata_rata';
    case IpkTerakhir = 'ipk_terakhir';
    case Tren = 'tren';
    case Konsistensi = 'konsistensi';

    // Non-akademik (skor SKKM F5–F7 + syarat jumlah prestasi per tingkat).
    case SkorPrestasi = 'skor_prestasi';
    case SkorKegiatan = 'skor_kegiatan';
    case SkorPengabdian = 'skor_pengabdian';
    case JumlahPrestasiMinTingkat = 'jumlah_prestasi_min_tingkat';

    // Administratif (kolom langsung pada tabel mahasiswa).
    case Status = 'status';
    case ProgramStudi = 'program_studi';
    case Angkatan = 'angkatan';
    case SemesterAktif = 'semester_aktif';

    // Hasil klasterisasi (label klaster dari eksekusi K-Means terbaru).
    case LabelKlaster = 'label_klaster';

    /** Label Bahasa Indonesia untuk dropdown & pratinjau. */
    public function label(): string
    {
        return match ($this) {
            self::IpkRataRata => 'IPK rata-rata',
            self::IpkTerakhir => 'IPK terakhir',
            self::Tren => 'Tren IPK',
            self::Konsistensi => 'Konsistensi IPK (deviasi)',
            self::SkorPrestasi => 'Skor prestasi (F5)',
            self::SkorKegiatan => 'Skor kegiatan/organisasi (F6)',
            self::SkorPengabdian => 'Skor pengabdian/hibah (F7)',
            self::JumlahPrestasiMinTingkat => 'Jumlah prestasi minimal pada suatu tingkat',
            self::Status => 'Status mahasiswa',
            self::ProgramStudi => 'Program studi',
            self::Angkatan => 'Angkatan',
            self::SemesterAktif => 'Semester aktif',
            self::LabelKlaster => 'Label klaster (K-Means)',
        };
    }

    /** Kelompok field untuk pengelompokan di antarmuka. */
    public function kategori(): string
    {
        return match ($this) {
            self::IpkRataRata, self::IpkTerakhir, self::Tren, self::Konsistensi => 'akademik',
            self::SkorPrestasi, self::SkorKegiatan, self::SkorPengabdian, self::JumlahPrestasiMinTingkat => 'non_akademik',
            self::Status, self::ProgramStudi, self::Angkatan, self::SemesterAktif => 'administratif',
            self::LabelKlaster => 'klasterisasi',
        };
    }

    /**
     * Tipe nilai: menentukan validasi input & cara perbandingan.
     * desimal | bilangan | pilihan | khusus.
     */
    public function tipe(): string
    {
        return match ($this) {
            self::IpkRataRata, self::IpkTerakhir, self::Tren, self::Konsistensi => 'desimal',
            self::SkorPrestasi, self::SkorKegiatan, self::SkorPengabdian,
            self::Angkatan, self::SemesterAktif => 'bilangan',
            self::Status, self::ProgramStudi, self::LabelKlaster => 'pilihan',
            self::JumlahPrestasiMinTingkat => 'khusus',
        };
    }

    /**
     * Operator yang valid untuk field ini. Form Request menolak kombinasi
     * field–operator di luar daftar ini.
     *
     * @return list<OperatorKriteria>
     */
    public function operatorValid(): array
    {
        return match ($this->tipe()) {
            'desimal', 'bilangan' => [
                OperatorKriteria::Gte, OperatorKriteria::Lte,
                OperatorKriteria::Gt, OperatorKriteria::Lt, OperatorKriteria::Eq,
            ],
            'pilihan' => [OperatorKriteria::Eq, OperatorKriteria::In],
            // 'khusus' (jumlah prestasi per tingkat): perbandingan ">=" atas
            // cacahan turunan, jadi hanya Gte yang bermakna.
            'khusus' => [OperatorKriteria::Gte],
            default => [],
        };
    }

    /**
     * Apakah field dapat diterjemahkan langsung ke klausa WHERE query builder
     * (kolom nyata pada tabel mahasiswa). Field turunan (IPK/skor/label klaster)
     * dievaluasi di PHP. Dipakai EvaluatorKelayakan untuk optimasi. Bahan BAB IV.
     */
    public function bisaQuery(): bool
    {
        return match ($this) {
            self::Status, self::Angkatan, self::SemesterAktif, self::ProgramStudi => true,
            default => false,
        };
    }

    /**
     * Opsi nilai baku untuk field bertipe 'pilihan' yang statis (status).
     * Field pilihan dinamis (program_studi, label_klaster) mengembalikan null;
     * opsinya diisi antarmuka dari basis data.
     *
     * @return array<string, string>|null [nilai => label]
     */
    public function opsiNilai(): ?array
    {
        return match ($this) {
            self::Status => [
                'aktif' => 'Aktif',
                'cuti' => 'Cuti',
                'non_aktif' => 'Non-aktif',
                'lulus' => 'Lulus',
                'do' => 'DO',
            ],
            default => null,
        };
    }

    /** Field bertipe 'pilihan' boleh memakai operator `in` (nilai jamak). */
    public function nilaiJamak(OperatorKriteria $operator): bool
    {
        return $operator === OperatorKriteria::In;
    }
}
