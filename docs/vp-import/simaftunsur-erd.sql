-- =====================================================================
-- SIMAFTUNSUR — DDL MySQL 8.x untuk ERD (khusus tabel bisnis)
-- Cara pakai di Visual Paradigm:
--   Tools > DB > Reverse DDL... > pilih file ini, database: MySQL
--   Entitas + relasi FK terbentuk otomatis; buat ERD lalu seret entitas.
--
-- Diturunkan dari database/migrations/ (2026-07-02).
-- Tabel sistem Laravel (sessions, cache, jobs, migrations,
-- password_reset_tokens) SENGAJA tidak disertakan sesuai ketentuan naskah.
-- =====================================================================

-- ============ AUTENTIKASI & SISTEM ============

CREATE TABLE pengguna (
    id                        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nip                       VARCHAR(32)  NOT NULL,
    nama                      VARCHAR(255) NOT NULL,
    email                     VARCHAR(255) NULL,
    kata_sandi                VARCHAR(255) NOT NULL,
    peran                     VARCHAR(32)  NOT NULL DEFAULT 'staf_prodi' COMMENT 'admin|wd3|staf_wd3|kaprodi|staf_prodi',
    email_terverifikasi_pada  TIMESTAMP NULL,
    remember_token            VARCHAR(100) NULL,
    created_at                TIMESTAMP NULL,
    updated_at                TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pengguna_nip (nip),
    UNIQUE KEY uq_pengguna_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pengaturan (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kunci       VARCHAR(255) NOT NULL,
    nilai       TEXT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pengaturan_kunci (kunci)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE log_aktivitas (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pengguna_id BIGINT UNSIGNED NULL,
    aksi        VARCHAR(32)  NOT NULL,
    model       VARCHAR(64)  NULL,
    deskripsi   VARCHAR(255) NULL,
    ip          VARCHAR(45)  NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_log_created (created_at),
    KEY idx_log_model (model),
    CONSTRAINT fk_log_pengguna FOREIGN KEY (pengguna_id)
        REFERENCES pengguna (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ DATA MAHASISWA (FOKUS PENELITIAN) ============

CREATE TABLE program_studi (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode        VARCHAR(8)   NOT NULL,
    nama        VARCHAR(255) NOT NULL,
    jenjang     ENUM('D3','D4','S1','S2') NOT NULL DEFAULT 'S1',
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_prodi_kode (kode)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mahasiswa (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    npm               VARCHAR(11)  NOT NULL,
    nama              VARCHAR(255) NOT NULL,
    program_studi_id  BIGINT UNSIGNED NOT NULL,
    angkatan          YEAR NOT NULL,
    semester_aktif    TINYINT UNSIGNED NOT NULL DEFAULT 1,
    jenis_kelamin     ENUM('L','P') NOT NULL,
    status            ENUM('aktif','cuti','non_aktif','lulus','do') NOT NULL DEFAULT 'aktif',
    status_akhir      ENUM('lulus_tepat','lulus_terlambat','do') NULL COMMENT 'Label pengembangan lanjutan (BAB V) - belum dipakai K-Means',
    email             VARCHAR(255) NULL,
    nomor_telepon     VARCHAR(20)  NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_mahasiswa_npm (npm),
    KEY idx_mhs_prodi_angkatan (program_studi_id, angkatan),
    KEY idx_mhs_status (status),
    CONSTRAINT fk_mhs_prodi FOREIGN KEY (program_studi_id)
        REFERENCES program_studi (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE nilai_ipk_semester (
    id                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mahasiswa_id          BIGINT UNSIGNED NOT NULL,
    semester              TINYINT UNSIGNED NOT NULL COMMENT '1-14',
    tahun_akademik        VARCHAR(9) NOT NULL COMMENT 'format 2025/2026',
    semester_ganjil_genap ENUM('ganjil','genap') NOT NULL,
    ipk                   DECIMAL(3,2) NOT NULL,
    sks_diambil           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    sks_lulus             SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at            TIMESTAMP NULL,
    updated_at            TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_ipk_mhs_semester (mahasiswa_id, semester),
    KEY idx_ipk_tahun (tahun_akademik),
    CONSTRAINT fk_ipk_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ KLASTERISASI K-MEANS (FOKUS PENELITIAN) ============

CREATE TABLE klasterisasi_eksekusi (
    id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    k_terpilih         TINYINT UNSIGNED NOT NULL,
    metode_pemilihan_k VARCHAR(255) NOT NULL,
    fitur_dipakai      JSON NOT NULL,
    skema_penskalaan   VARCHAR(16) NOT NULL DEFAULT 'standard',
    random_state       INT NULL,
    versi_algoritma    VARCHAR(255) NULL,
    kriteria_data      VARCHAR(255) NULL,
    jumlah_data        INT UNSIGNED NOT NULL,
    silhouette         DECIMAL(6,4) NULL,
    davies_bouldin     DECIMAL(6,4) NULL,
    inertia            DOUBLE NULL,
    evaluasi_k         JSON NOT NULL,
    profil_klaster     JSON NOT NULL,
    peringatan         JSON NULL,
    dijalankan_oleh    BIGINT UNSIGNED NULL,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_eksekusi_pengguna FOREIGN KEY (dijalankan_oleh)
        REFERENCES pengguna (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE klasterisasi_klaster (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    eksekusi_id       BIGINT UNSIGNED NOT NULL,
    cluster           TINYINT UNSIGNED NOT NULL,
    label_deskriptif  VARCHAR(255) NULL,
    jumlah_anggota    INT UNSIGNED NOT NULL DEFAULT 0,
    centroid          JSON NOT NULL COMMENT 'nilai centroid dalam satuan asli',
    centroid_terskala JSON NULL,
    skor_akademik     DOUBLE NULL COMMENT 'skor komponen akademik (F1-F4)',
    skor_non_akademik DOUBLE NULL COMMENT 'skor komponen non-akademik (F5-F7)',
    skor_komposit     DOUBLE NULL COMMENT 'dasar peringkat penamaan klaster',
    ringkasan_profil  TEXT NULL COMMENT 'alasan penamaan (transparansi label)',
    interpretasi      TEXT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_klaster_eksekusi_cluster (eksekusi_id, cluster),
    CONSTRAINT fk_klaster_eksekusi FOREIGN KEY (eksekusi_id)
        REFERENCES klasterisasi_eksekusi (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Master katalog nama/label klaster. Dipetakan ke klaster hasil K-Means
-- berdasarkan PERINGKAT skor komposit; jumlah klaster tetap dinamis dari
-- algoritma. Tidak diikat foreign key ke klasterisasi_klaster karena
-- katalog dikirim ke service Python sebagai konfigurasi saat eksekusi.
CREATE TABLE klasterisasi_kategori (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama        VARCHAR(255) NOT NULL,
    urutan      TINYINT UNSIGNED NOT NULL DEFAULT 1
                COMMENT 'peringkat skor komposit: 1 = tertinggi',
    deskripsi   TEXT NULL,
    rekomendasi TEXT NULL COMMENT 'rekomendasi pembinaan untuk kategori ini',
    warna       VARCHAR(16) NULL COMMENT 'token warna dashboard, mis. cluster-1',
    aktif       BOOLEAN NOT NULL DEFAULT TRUE,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kategori_nama (nama),
    KEY idx_kategori_urutan (urutan),
    KEY idx_kategori_aktif (aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE klasterisasi_anggota (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    eksekusi_id       BIGINT UNSIGNED NOT NULL,
    klaster_id        BIGINT UNSIGNED NULL,
    mahasiswa_id      BIGINT UNSIGNED NOT NULL,
    cluster           TINYINT UNSIGNED NOT NULL,
    fitur_nilai       JSON NULL COMMENT 'snapshot fitur satuan asli saat eksekusi',
    fitur_terskala    JSON NULL,
    jarak_ke_centroid DOUBLE NULL,
    pca_x             DOUBLE NOT NULL DEFAULT 0,
    pca_y             DOUBLE NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY uq_anggota_eksekusi_mhs (eksekusi_id, mahasiswa_id),
    KEY idx_anggota_eksekusi_cluster (eksekusi_id, cluster),
    CONSTRAINT fk_anggota_eksekusi FOREIGN KEY (eksekusi_id)
        REFERENCES klasterisasi_eksekusi (id) ON DELETE CASCADE,
    CONSTRAINT fk_anggota_klaster FOREIGN KEY (klaster_id)
        REFERENCES klasterisasi_klaster (id) ON DELETE SET NULL,
    CONSTRAINT fk_anggota_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ MODUL PENDUKUNG: PRESTASI & SKKM ============

CREATE TABLE prestasi (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mahasiswa_id  BIGINT UNSIGNED NOT NULL,
    judul         VARCHAR(255) NOT NULL,
    jenis         ENUM('akademik','non_akademik') NOT NULL DEFAULT 'akademik',
    tingkat       ENUM('lokal','regional','nasional','internasional') NOT NULL DEFAULT 'lokal',
    capaian       ENUM('juara_1','juara_2','juara_3','finalis') NULL COMMENT 'penentu poin F5',
    peringkat     VARCHAR(255) NULL,
    penyelenggara VARCHAR(255) NULL,
    tanggal       DATE NULL,
    url_bukti     VARCHAR(255) NULL,
    berkas_bukti  VARCHAR(255) NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_prestasi_jenis_tingkat (jenis, tingkat),
    KEY idx_prestasi_mhs (mahasiswa_id),
    CONSTRAINT fk_prestasi_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kegiatan_kemahasiswaan (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mahasiswa_id  BIGINT UNSIGNED NOT NULL,
    jenis         ENUM('organisasi','kepanitiaan','seminar') NOT NULL,
    peran         VARCHAR(255) NOT NULL,
    nama_kegiatan VARCHAR(255) NOT NULL,
    penyelenggara VARCHAR(255) NULL,
    periode       VARCHAR(255) NULL,
    tanggal       DATE NULL,
    url_bukti     VARCHAR(255) NULL,
    keterangan    TEXT NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_kegiatan_mhs (mahasiswa_id),
    KEY idx_kegiatan_jenis (jenis),
    CONSTRAINT fk_kegiatan_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE pengabdian_hibah (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mahasiswa_id BIGINT UNSIGNED NOT NULL,
    jenis        ENUM('hibah_didanai','proposal_lolos','pengabdian_masyarakat') NOT NULL,
    peran        VARCHAR(255) NOT NULL,
    judul        VARCHAR(255) NOT NULL,
    sumber_dana  VARCHAR(255) NULL,
    tahun        YEAR NULL,
    url_bukti    VARCHAR(255) NULL,
    keterangan   TEXT NULL,
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_pengabdian_mhs (mahasiswa_id),
    KEY idx_pengabdian_jenis (jenis),
    CONSTRAINT fk_pengabdian_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ MODUL PENDUKUNG: TRACER STUDY ============

CREATE TABLE tracer_study (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mahasiswa_id      BIGINT UNSIGNED NOT NULL,
    tahun_lulus       YEAR NULL,
    status_pekerjaan  ENUM('bekerja','wirausaha','lanjut_studi','belum_bekerja') NOT NULL DEFAULT 'belum_bekerja',
    masa_tunggu_bulan SMALLINT UNSIGNED NULL,
    nama_instansi     VARCHAR(255) NULL,
    relevansi         ENUM('sangat_relevan','relevan','kurang_relevan','tidak_relevan') NULL,
    rentang_gaji      VARCHAR(255) NULL,
    tanggal_isi       DATE NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_tracer_status (status_pekerjaan),
    KEY idx_tracer_tahun (tahun_lulus),
    CONSTRAINT fk_tracer_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ MODUL PENDUKUNG: BEASISWA ============

CREATE TABLE beasiswa_kategori (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kode          VARCHAR(30)  NOT NULL,
    nama          VARCHAR(255) NOT NULL,
    jenis_bantuan ENUM('ukt','biaya_hidup','total') NOT NULL DEFAULT 'ukt',
    sumber_dana   ENUM('ftunsur','lldikti','kemendikti') NOT NULL DEFAULT 'ftunsur',
    aktif         TINYINT(1) NOT NULL DEFAULT 1,
    keterangan    TEXT NULL,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bkat_kode (kode),
    KEY idx_bkat_aktif (aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE beasiswa_penerima (
    id                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    mahasiswa_id         BIGINT UNSIGNED NOT NULL,
    beasiswa_kategori_id BIGINT UNSIGNED NOT NULL,
    tahun_akademik       VARCHAR(9) NOT NULL,
    semester             ENUM('ganjil','genap') NOT NULL DEFAULT 'ganjil',
    status               ENUM('diusulkan','diverifikasi','ditetapkan','ditolak','selesai','dibekukan') NOT NULL DEFAULT 'diusulkan',
    nominal              DECIMAL(12,2) NULL,
    no_sk                VARCHAR(255) NULL,
    tanggal_sk           DATE NULL,
    sumber_usulan        VARCHAR(255) NULL,
    keterangan           TEXT NULL,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_bpen_status (status),
    KEY idx_bpen_tahun (tahun_akademik),
    KEY idx_bpen_mhs (mahasiswa_id),
    CONSTRAINT fk_bpen_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE,
    CONSTRAINT fk_bpen_kategori FOREIGN KEY (beasiswa_kategori_id)
        REFERENCES beasiswa_kategori (id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ MODUL PENDUKUNG: KKN ============

CREATE TABLE kkn_lokasi (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama           VARCHAR(255) NOT NULL,
    kecamatan      VARCHAR(255) NULL,
    kabupaten      VARCHAR(255) NULL DEFAULT 'Cianjur',
    tahun_akademik VARCHAR(9) NOT NULL,
    kuota          SMALLINT UNSIGNED NULL,
    mitra          VARCHAR(255) NULL,
    aktif          TINYINT(1) NOT NULL DEFAULT 1,
    keterangan     TEXT NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_klok_tahun (tahun_akademik),
    KEY idx_klok_aktif (aktif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kkn_dpl (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama            VARCHAR(255) NOT NULL,
    nip             VARCHAR(255) NULL,
    nomor_telepon   VARCHAR(255) NULL,
    bidang_keahlian VARCHAR(255) NULL,
    aktif           TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kkn_kelompok (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_kelompok  VARCHAR(255) NOT NULL,
    kkn_lokasi_id  BIGINT UNSIGNED NOT NULL,
    kkn_dpl_id     BIGINT UNSIGNED NULL,
    tahun_akademik VARCHAR(9) NOT NULL,
    status         ENUM('persiapan','berjalan','selesai') NOT NULL DEFAULT 'persiapan',
    keterangan     TEXT NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_kkel_status (status),
    KEY idx_kkel_tahun (tahun_akademik),
    CONSTRAINT fk_kkel_lokasi FOREIGN KEY (kkn_lokasi_id)
        REFERENCES kkn_lokasi (id) ON DELETE RESTRICT,
    CONSTRAINT fk_kkel_dpl FOREIGN KEY (kkn_dpl_id)
        REFERENCES kkn_dpl (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE kkn_peserta (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    kkn_kelompok_id BIGINT UNSIGNED NOT NULL,
    mahasiswa_id    BIGINT UNSIGNED NOT NULL,
    jabatan         ENUM('ketua','sekretaris','bendahara','anggota') NOT NULL DEFAULT 'anggota',
    status          ENUM('terdaftar','aktif','selesai','mengundurkan_diri') NOT NULL DEFAULT 'terdaftar',
    nilai_akhir     DECIMAL(5,2) NULL,
    nilai_huruf     VARCHAR(2) NULL,
    catatan         TEXT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_kpes_kelompok_mhs (kkn_kelompok_id, mahasiswa_id),
    KEY idx_kpes_mhs (mahasiswa_id),
    KEY idx_kpes_status (status),
    CONSTRAINT fk_kpes_kelompok FOREIGN KEY (kkn_kelompok_id)
        REFERENCES kkn_kelompok (id) ON DELETE CASCADE,
    CONSTRAINT fk_kpes_mhs FOREIGN KEY (mahasiswa_id)
        REFERENCES mahasiswa (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============ MODUL PENDUKUNG: PROMOSI/PMB ============

CREATE TABLE kegiatan_promosi (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama_kegiatan  VARCHAR(255) NOT NULL,
    sekolah_target VARCHAR(255) NOT NULL,
    kota           VARCHAR(255) NULL,
    tanggal        DATE NULL,
    petugas        VARCHAR(255) NULL,
    jumlah_peminat INT UNSIGNED NULL,
    catatan        TEXT NULL,
    created_at     TIMESTAMP NULL,
    updated_at     TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_promosi_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sekolah (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama       VARCHAR(255) NOT NULL,
    jenjang    ENUM('SMA','SMK','MA','Lainnya') NOT NULL DEFAULT 'SMA',
    kota       VARCHAR(255) NULL,
    alamat     VARCHAR(255) NULL,
    kontak     VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_sekolah_kota (kota)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
