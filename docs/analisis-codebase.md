# Analisis Codebase SIMAFTUNSUR

> Diturunkan dari codebase nyata: `app/Models/`, `app/Http/`, `routes/web.php`, `config/peran.php`, migrations, Fortify.
> Terakhir diperbarui: 2026-06-24. Render Mermaid di <https://mermaid.live> atau VS Code (ekstensi *Markdown Preview Mermaid Support*).

---

## 1. ERD — `erDiagram` (hanya tabel domain bisnis)

Tabel bawaan Laravel (`migrations`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`) **diabaikan**. Tersisa 4 tabel bisnis.

```mermaid
erDiagram
    PROGRAM_STUDI ||--o{ MAHASISWA : "memiliki (1..*)"
    MAHASISWA ||--o{ NILAI_IPK_SEMESTER : "mencatat (1..*)"

    PROGRAM_STUDI {
        bigint id PK "auto-increment"
        string kode UK "varchar(8), mis. TIF"
        string nama "varchar(255)"
        enum jenjang "D3|D4|S1|S2, default S1"
        timestamp created_at
        timestamp updated_at
    }

    MAHASISWA {
        bigint id PK
        string npm UK "varchar(11), Nomor Pokok Mahasiswa"
        string nama "varchar(255)"
        bigint program_studi_id FK "-> program_studi.id, restrictOnDelete"
        year angkatan
        tinyint semester_aktif "unsigned, 1-14, default 1"
        enum jenis_kelamin "L|P"
        enum status "aktif|cuti|non_aktif|lulus|do, default aktif"
        enum status_akhir "lulus_tepat|lulus_terlambat|do, NULLABLE (label RF, belum dipakai)"
        string email "nullable"
        string nomor_telepon "varchar(20), nullable"
        timestamp created_at
        timestamp updated_at
    }

    NILAI_IPK_SEMESTER {
        bigint id PK
        bigint mahasiswa_id FK "-> mahasiswa.id, cascadeOnDelete"
        tinyint semester "unsigned, 1-14"
        string tahun_akademik "varchar(9), format 2025/2026"
        enum semester_ganjil_genap "ganjil|genap"
        decimal ipk "decimal(3,2)"
        smallint sks_diambil "unsigned, default 0"
        smallint sks_lulus "unsigned, default 0"
        timestamp created_at
        timestamp updated_at
    }

    PENGGUNA {
        bigint id PK
        string nip UK "varchar(32), NIP/NIDN — identitas login"
        string nama "varchar(255)"
        string email UK "nullable"
        string kata_sandi "hashed"
        string peran "varchar(32), default staf"
        timestamp email_terverifikasi_pada "nullable"
        string remember_token "varchar(100), nullable"
        timestamp created_at
        timestamp updated_at
    }
```

**Kardinalitas & constraint:**
- `program_studi (1) —— (0..*) mahasiswa` — FK `restrictOnDelete` (prodi tak bisa dihapus selama masih punya mahasiswa), `cascadeOnUpdate`.
- `mahasiswa (1) —— (0..*) nilai_ipk_semester` — FK `cascadeOnDelete` + `cascadeOnUpdate`.
- Unik gabungan: `nilai_ipk_semester (mahasiswa_id, semester)` — 1 catatan per semester per mahasiswa.
- Index: `mahasiswa (program_studi_id, angkatan)` & `mahasiswa (status)`; `nilai_ipk_semester (tahun_akademik)`.
- `pengguna` **berdiri sendiri** — RBAC tidak pakai tabel (`roles`/`permissions`), melainkan peta statis di `config/peran.php`. Tidak ada relasi FK dari `pengguna`.

---

## 2. Class Diagram — `classDiagram` (Models + Controller)

```mermaid
classDiagram
    class Pengguna {
        <<Authenticatable>>
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
        #daftarIzinPeran() array
    }

    class ProgramStudi {
        <<Model>>
        +string kode
        +string nama
        +string jenjang
        +mahasiswa() HasMany
    }

    class Mahasiswa {
        <<Model>>
        +string npm
        +string nama
        +int program_studi_id
        +int angkatan
        +int semester_aktif
        +enum jenis_kelamin
        +enum status
        +enum status_akhir
        +programStudi() BelongsTo
        +nilaiIpkSemester() HasMany
        +ipkRataRata() float
        +ipkTerakhir() float
        +tren() float
        +konsistensi() float
    }

    class NilaiIpkSemester {
        <<Model>>
        +int mahasiswa_id
        +int semester
        +string tahun_akademik
        +enum semester_ganjil_genap
        +decimal ipk
        +int sks_diambil
        +int sks_lulus
        +mahasiswa() BelongsTo
    }

    class TemplateIpkController {
        <<Controller>>
        +unduh(Request) StreamedResponse
    }

    class IpkMassalImport {
        <<Import: WithHeadingRow, SkipsOnError>>
        +collection(rows) void
        +hasil() HasilImpor
    }
    class IpkSatuMahasiswaImport {
        <<Import>>
        +collection(rows) void
    }

    ProgramStudi "1" --o "0..*" Mahasiswa : hasMany / belongsTo
    Mahasiswa "1" --o "0..*" NilaiIpkSemester : hasMany / belongsTo
    TemplateIpkController ..> Pengguna : can('mahasiswa.kelola')
    IpkMassalImport ..> Mahasiswa : cari via npm
    IpkMassalImport ..> NilaiIpkSemester : upsert
```

**Method penting "controller" (CRUD mahasiswa ditangani komponen Volt, bukan Controller klasik):**

| Page-controller (Volt) | Method/aksi penting | Guard |
|---|---|---|
| `mahasiswa/index` | `daftarProdi()`, `daftarAngkatan()`, filter `kataKunci/filterProdi/filterAngkatan/filterStatus`, `bersihkanFilter()`, pagination | auth (lihat); tombol kelola `@can('mahasiswa.kelola')` |
| `mahasiswa/baru` | `simpan()` (validasi + create) | `abort_unless can('mahasiswa.kelola')` di `mount()` |
| `mahasiswa/ubah` | `simpan()` (update) | `abort_unless can('mahasiswa.kelola')` |
| `mahasiswa/detail` | tambah/ubah/hapus IPK, lihat tren | `abort_unless can('mahasiswa.kelola')` per aksi |
| `mahasiswa/impor` | proses upload Excel → `IpkMassalImport` | `abort_unless can('mahasiswa.kelola')` |
| `TemplateIpkController@unduh` | generate CSV template (mode massal/satu) | `abort_unless can('mahasiswa.kelola')` |

---

## 3. Aktor, Hak Akses & Use Case

### 3a. Aktor & peta izin (`config/peran.php`)

| Aktor (peran) | Izin terdefinisi |
|---|---|
| **Administrator** (`admin`) | `*` — seluruh izin (wildcard, di-resolve `Pengguna::punyaIzin()`) |
| **Wakil Dekan III** (`wd3`) | `mahasiswa.kelola`, `klasterisasi.lihat`, `klasterisasi.jalankan`, `laporan.lihat`, `laporan.ekspor` |
| **Staf Kemahasiswaan** (`staf`) | `mahasiswa.kelola`, `ipk.kelola` |
| **Ketua Program Studi** (`kaprodi`) | `mahasiswa.lihat`, `laporan.lihat` |
| **Dekan** (`dekan`) | `mahasiswa.lihat`, `klasterisasi.lihat`, `laporan.lihat` |
| **Dosen** (`dosen`) | `mahasiswa.lihat` |

> Izin dipasang ke Gate Laravel di `AppServiceProvider` (`Gate::define($kode, fn => $pengguna->punyaIzin($kode))`), sehingga `@can('...')` dan `->can('...')` aktif.

### 3b. Use case per aktor (diturunkan dari `routes/web.php` + guard nyata)

Legenda guard: **auth** = cukup login · **kelola** = `can('mahasiswa.kelola')` (abort_unless) · **peran** = middleware `peran:admin,wd3` · 🔒 *rencana* = izin sudah didefinisikan tapi route/fitur belum dibangun.

| Use case | Route / sumber | Guard nyata di kode | admin | wd3 | staf | kaprodi | dekan | dosen |
|---|---|---|:--:|:--:|:--:|:--:|:--:|:--:|
| Masuk / Keluar | Fortify `login`/`logout` | tamu / auth | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Lihat Dashboard | `beranda` | auth | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Lihat Data Mahasiswa | `mahasiswa.index` `.detail` | auth¹ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Tambah Mahasiswa | `mahasiswa.baru` | kelola | ✅ | ✅ | ✅ | — | — | — |
| Ubah Mahasiswa | `mahasiswa.ubah` | kelola | ✅ | ✅ | ✅ | — | — | — |
| Kelola IPK per semester | `mahasiswa.detail` (aksi) | kelola | ✅ | ✅ | ✅ | — | — | — |
| Impor IPK Massal (Excel) | `mahasiswa.ipk.impor` | kelola | ✅ | ✅ | ✅ | — | — | — |
| Unduh Template IPK | `mahasiswa.ipk.template` | kelola | ✅ | ✅ | ✅ | — | — | — |
| (uji) Demo Peran | `demo.peran` | peran:admin,wd3² | ✅ | ✅ | — | — | — | — |
| 🔒 Jalankan Klasterisasi K-Means | *belum ada route* | `klasterisasi.jalankan` | ✅ | ✅ | — | — | — | — |
| 🔒 Lihat Hasil Klasterisasi | *belum ada route* | `klasterisasi.lihat` | ✅ | ✅ | — | — | ✅ | — |
| 🔒 Lihat / Ekspor Laporan | *belum ada route* | `laporan.lihat` / `.ekspor` | ✅ | ✅ | — | ✅ | ✅ | — |

¹ **Catatan keamanan:** route `mahasiswa.index`/`.detail` saat ini hanya bermiddleware `auth` — **belum** menegakkan `mahasiswa.lihat` di level route. Semua peran login bisa membaca. Jika ingin tegas sesuai matriks, tambahkan gate `mahasiswa.lihat` di `mount()` kedua komponen.
² **`demo.peran`** adalah route scaffolding RBAC dan ditandai akan dihapus saat modul nyata jalan.

> **Inkonsistensi minor:** izin `ipk.kelola` (untuk `staf`) terdefinisi tapi tidak pernah dicek — pengelolaan IPK memakai gate `mahasiswa.kelola`. Fungsional benar (staf punya keduanya), tapi pertimbangkan menyatukan agar peta izin bersih.
