# Sumber Teks Diagram BAB III — SIMAFTUNSUR

> **Tujuan:** sumber teks (Mermaid) untuk diagram perancangan sistem pada BAB III, **diturunkan dari codebase nyata** (bukan skema asumsi). Mudah direvisi pembimbing & dirender ulang (mis. di mermaid.live, VS Code Mermaid Preview, atau diekspor PNG/SVG untuk dokumen).
>
> **Diselaraskan dengan:** migrations `database/migrations/`, Models `app/Models/`, `routes/web.php`, `config/peran.php`, middleware `app/Http/Middleware/Peran.php`, dan Fortify.
> **Terakhir diperbarui:** 2026-06-24.
>
> **Cakupan:** hanya tabel/relasi **bisnis**. Tabel sistem Laravel (`cache`, `jobs`, `sessions`, `password_reset_tokens`, `migrations`) **sengaja dibuang** sesuai ketentuan naskah.

---

## 1. ERD — Entity Relationship Diagram (tabel bisnis)

Empat tabel bisnis: `program_studi`, `mahasiswa`, `nilai_ipk_semester`, `pengguna`.
Kolom `status_akhir` pada `mahasiswa` disiapkan untuk pengembangan lanjutan (Random Forest, BAB V) dan **belum dipakai** pada klasterisasi K-Means.

```mermaid
erDiagram
    PROGRAM_STUDI ||--o{ MAHASISWA : "memiliki"
    MAHASISWA ||--o{ NILAI_IPK_SEMESTER : "mencatat"

    PROGRAM_STUDI {
        bigint id PK
        string kode UK "mis. TIF, TSI"
        string nama
        enum   jenjang "D3|D4|S1|S2"
        timestamp created_at
        timestamp updated_at
    }

    MAHASISWA {
        bigint id PK
        string npm UK "Nomor Pokok Mahasiswa"
        string nama
        bigint program_studi_id FK
        year   angkatan
        tinyint semester_aktif "1-14"
        enum   jenis_kelamin "L|P"
        enum   status "aktif|cuti|non_aktif|lulus|do"
        enum   status_akhir "lulus_tepat|lulus_terlambat|do (nullable, label RF — belum dipakai)"
        string email "nullable"
        string nomor_telepon "nullable"
        timestamp created_at
        timestamp updated_at
    }

    NILAI_IPK_SEMESTER {
        bigint id PK
        bigint mahasiswa_id FK
        tinyint semester "1-14"
        string tahun_akademik "format 2025/2026"
        enum   semester_ganjil_genap "ganjil|genap"
        decimal ipk "3,2"
        smallint sks_diambil
        smallint sks_lulus
        timestamp created_at
        timestamp updated_at
    }

    PENGGUNA {
        bigint id PK
        string nip UK "NIP/NIDN — identitas masuk"
        string nama
        string email UK "nullable"
        string kata_sandi "hashed"
        string peran "admin|dekan|wd3|kaprodi|staf|dosen"
        timestamp email_terverifikasi_pada "nullable"
        string remember_token
        timestamp created_at
        timestamp updated_at
    }
```

**Catatan relasi:**
- `mahasiswa.program_studi_id` → `program_studi.id` (`restrictOnDelete` — prodi tak bisa dihapus bila masih punya mahasiswa).
- `nilai_ipk_semester.mahasiswa_id` → `mahasiswa.id` (`cascadeOnDelete` — hapus mahasiswa ikut menghapus riwayat IPK).
- `pengguna` berdiri sendiri (entitas autentikasi/RBAC), tidak ber-FK ke entitas bisnis. RBAC dipetakan statis di `config/peran.php` (bukan tabel), sehingga **tidak ada tabel `roles`/`permissions`**.
- Constraint unik gabungan: `nilai_ipk_semester (mahasiswa_id, semester)` — satu mahasiswa hanya boleh punya satu catatan per semester.

---

## 2. Class Diagram (Model Eloquent)

Mencerminkan kelas pada `app/Models/`, atribut utama, dan **method domain** yang menyiapkan fitur klasterisasi (rata-rata, tren, konsistensi IPK).

```mermaid
classDiagram
    class Pengguna {
        +string nip
        +string nama
        +string email
        +string kata_sandi
        +string peran
        +getAuthPasswordName() string
        +inisial() string
        +labelPeran() string
        +punyaIzin(kode) bool
        +punyaPeran(peran) bool
        +semuaIzin() array
    }

    class ProgramStudi {
        +string kode
        +string nama
        +string jenjang
        +mahasiswa() HasMany
    }

    class Mahasiswa {
        +string npm
        +string nama
        +int program_studi_id
        +int angkatan
        +int semester_aktif
        +string jenis_kelamin
        +string status
        +string status_akhir
        +programStudi() BelongsTo
        +nilaiIpkSemester() HasMany
        +ipkRataRata() float
        +ipkTerakhir() float
        +tren() float
        +konsistensi() float
    }

    class NilaiIpkSemester {
        +int mahasiswa_id
        +int semester
        +string tahun_akademik
        +string semester_ganjil_genap
        +decimal ipk
        +int sks_diambil
        +int sks_lulus
        +mahasiswa() BelongsTo
    }

    ProgramStudi "1" --o "0..*" Mahasiswa : memiliki
    Mahasiswa "1" --o "0..*" NilaiIpkSemester : mencatat
```

**Catatan fitur klasterisasi (sudah ada di Model `Mahasiswa`):**
- `ipkRataRata()` — rata-rata IPK seluruh semester.
- `tren()` — slope regresi linear sederhana terhadap urutan semester (positif = naik, negatif = turun). *Catatan: ini perhitungan tren deskriptif untuk fitur, **bukan** Regresi Linier sebagai metode prediksi — metode inti tetap K-Means.*
- `konsistensi()` — standar deviasi populasi IPK (kecil = stabil, besar = fluktuatif).

Ketiga method ini menjadi **kandidat fitur** yang diekspor ke pipeline K-Means.

---

## 3. Use Case Diagram

Aktor diturunkan dari peran di `config/peran.php`; use case dari `routes/web.php` + izin (`@can` / `abort_unless`) + Fortify (login/logout).
Use case **bergaris putus-putus = direncanakan** (modul masih placeholder/disabled di sidebar): Klasterisasi & Laporan.

```mermaid
flowchart LR
    %% Aktor
    admin([Administrator])
    wd3([Wakil Dekan III])
    staf([Staf Kemahasiswaan])
    kaprodi([Ketua Program Studi])
    dekan([Dekan])
    dosen([Dosen])

    %% Use case terimplementasi
    UC_login(["Masuk / Keluar"])
    UC_lihat(["Lihat Data Mahasiswa"])
    UC_kelola(["Kelola Data Mahasiswa\n(tambah/ubah/hapus)"])
    UC_ipk(["Kelola IPK per Semester"])
    UC_impor(["Impor IPK Massal (Excel)"])
    UC_template(["Unduh Template IPK"])
    UC_beranda(["Lihat Dashboard Ringkas"])

    %% Use case direncanakan
    UC_klihat(["Lihat Hasil Klasterisasi"]):::plan
    UC_kjalan(["Jalankan Klasterisasi K-Means"]):::plan
    UC_lap(["Lihat / Ekspor Laporan"]):::plan

    %% Semua aktor bisa login & lihat dashboard
    admin --- UC_login
    wd3 --- UC_login
    staf --- UC_login
    kaprodi --- UC_login
    dekan --- UC_login
    dosen --- UC_login
    admin --- UC_beranda
    wd3 --- UC_beranda
    staf --- UC_beranda
    kaprodi --- UC_beranda
    dekan --- UC_beranda
    dosen --- UC_beranda

    %% mahasiswa.lihat
    dekan --- UC_lihat
    kaprodi --- UC_lihat
    dosen --- UC_lihat
    admin --- UC_lihat
    wd3 --- UC_lihat

    %% mahasiswa.kelola (admin, wd3, staf)
    admin --- UC_kelola
    wd3 --- UC_kelola
    staf --- UC_kelola
    admin --- UC_ipk
    wd3 --- UC_ipk
    staf --- UC_ipk
    admin --- UC_impor
    wd3 --- UC_impor
    staf --- UC_impor
    admin --- UC_template
    wd3 --- UC_template
    staf --- UC_template

    %% klasterisasi (admin, wd3 jalankan; dekan/kaprodi lihat) — direncanakan
    wd3 -.-> UC_kjalan
    admin -.-> UC_kjalan
    wd3 -.-> UC_klihat
    dekan -.-> UC_klihat
    admin -.-> UC_klihat

    %% laporan — direncanakan
    wd3 -.-> UC_lap
    dekan -.-> UC_lap
    kaprodi -.-> UC_lap
    admin -.-> UC_lap

    classDef plan stroke-dasharray: 5 5,fill:#fff7ed,stroke:#d97706;
```

**Pemetaan aktor ↔ izin (`config/peran.php`):**

| Aktor (peran) | Izin terdefinisi |
|---|---|
| Administrator (`admin`) | `*` (seluruh izin) |
| Wakil Dekan III (`wd3`) | `mahasiswa.kelola`, `klasterisasi.lihat`, `klasterisasi.jalankan`, `laporan.lihat`, `laporan.ekspor` |
| Staf Kemahasiswaan (`staf`) | `mahasiswa.kelola`, `ipk.kelola` |
| Ketua Program Studi (`kaprodi`) | `mahasiswa.lihat`, `laporan.lihat` |
| Dekan (`dekan`) | `mahasiswa.lihat`, `klasterisasi.lihat`, `laporan.lihat` |
| Dosen (`dosen`) | `mahasiswa.lihat` |

> **Catatan konsistensi:** izin `ipk.kelola` terdefinisi untuk `staf`, tetapi gate nyata pada pengelolaan IPK di `detail.blade.php` memakai `mahasiswa.kelola` (yang juga dimiliki staf), sehingga fungsional tetap benar. Pertimbangkan menyatukan keduanya saat membangun modul IPK lanjutan.

---

## 4. Activity Diagram — Alur Klasterisasi K-Means

Memetakan tahapan penerapan K-Means (naskah BAB III, kerangka CRISP-DM yang terintegrasi ke Waterfall). Menggambarkan pembagian tanggung jawab: **Laravel (SI)** ↔ **Python service (scikit-learn)**.

> Status: pipeline ini **belum diimplementasikan** (rencana tahap berikutnya). Diagram ini = rancangan acuan untuk BAB III.

```mermaid
flowchart TD
    A([Mulai: WD III/Admin pilih 'Jalankan Klasterisasi']) --> B[Laravel: ambil data mahasiswa aktif ≥ 3 semester\n+ riwayat IPK]
    B --> C{Volume data cukup?\nmin. 100 mahasiswa}
    C -- Tidak --> C1[Tampilkan peringatan:\ndata belum memadai] --> Z([Selesai])
    C -- Ya --> D[Bentuk fitur:\nIPK rata-rata, tren, konsistensi,\nIPK terakhir, semester, prodi]
    D --> E[Kirim dataset ke Python service via REST API]

    subgraph PY [Python service - scikit-learn]
        F[preprocess: tangani missing value,\noutlier, encoding] --> G[feature_engineering:\nStandardScaler / MinMax]
        G --> H[Tentukan k optimal:\nElbow WCSS + Silhouette]
        H --> I[train_kmeans: KMeans++ init]
        I --> J[evaluate: Silhouette + Davies-Bouldin Index]
        J --> K{Metrik memadai?}
        K -- Tidak --> H
        K -- Ya --> L[interpret_clusters:\nkarakteristik centroid + PCA 2D]
    end

    E --> F
    L --> M[Kembalikan label klaster + metrik + koordinat visual ke Laravel]
    M --> N[Laravel: simpan hasil klaster ke basis data]
    N --> O[Tampilkan dashboard:\nsebaran klaster, profil tiap klaster,\nmetrik evaluasi, scatter PCA]
    O --> P[WD III: interpretasi untuk strategi pembinaan]
    P --> Z([Selesai])
```

**Catatan teknis:**
- Atribut fitur sumber: method `ipkRataRata()`, `tren()`, `konsistensi()`, `ipkTerakhir()` pada Model `Mahasiswa` + `semester_aktif` + `program_studi_id`.
- Evaluasi **internal** (unsupervised): Silhouette Coefficient, Davies-Bouldin Index, Elbow (WCSS) — **bukan** Accuracy/Precision/Recall.
- Penentuan `k` memakai kombinasi Elbow + Silhouette (sesuai naskah).
- Kolom `status_akhir` **tidak** dijadikan fitur klasterisasi (itu label untuk RF, BAB V).

---

## Cara render / ekspor

- **Online:** salin tiap blok ```mermaid``` ke <https://mermaid.live> → ekspor PNG/SVG.
- **VS Code:** ekstensi *Markdown Preview Mermaid Support*.
- **CLI (opsional):** `mmdc -i docs/diagram-bab3.md -o keluaran.svg` (paket `@mermaid-js/mermaid-cli`).

> Untuk dokumen Word: render ke SVG/PNG lalu sisipkan; simpan sumber `.md` ini sebagai lampiran agar pembimbing dapat meminta revisi pada teksnya, bukan pada gambar.
