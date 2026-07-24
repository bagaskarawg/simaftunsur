# Modul Penyaringan Kandidat Berbasis Persyaratan Program

> Dokumentasi teknis untuk BAB III–IV. Modul ini adalah **pemanfaatan operasional
> hasil klasterisasi** (bukan fokus penelitian, bukan sistem pendukung keputusan).
> Beasiswa hanyalah salah satu jenis program yang disaring — modul pendukung.

---

## 1. Deskripsi Use Case

**Aktor:**

- **Wakil Dekan III (WD III)** & **Staf WD III** — mendefinisikan program & persyaratan
  (CRUD), membuka halaman penyaringan, mengekspor daftar kandidat.
- **Administrator** — akses penuh (superset).
- **Kaprodi** — hanya melihat daftar program (read-only), tidak menyaring.

**Alur utama (Penyaringan Kandidat):**

1. Pengguna memilih satu program pada halaman Penyaringan Kandidat.
2. Sistem menampilkan ringkasan syarat program (kriteria + badge wajib/opsional).
3. Sistem mengevaluasi tiap mahasiswa terhadap seluruh syarat secara **independen**
   (boolean lolos/tidak), lalu menampilkan yang **memenuhi seluruh syarat wajib**.
4. Kolom ✓/✗ menampilkan status tiap syarat; pengurutan hanya per satu kolom data
   mentah (IPK, poin, dsb).
5. Pengguna dapat mengekspor daftar (CSV) sebagai bahan keputusan manual WD III.

**Alur alternatif:**

- **Program tanpa syarat wajib** → konjungsi atas himpunan kosong bernilai benar,
  sehingga seluruh mahasiswa dianggap memenuhi; UI menampilkan peringatan.
- **Mahasiswa belum diklaster** → kriteria `label_klaster` dinilai *tidak lolos*
  dengan keterangan "belum diklaster" (tidak error, tidak diasumsikan lolos).
- **Mode audit** → toggle "Tampilkan yang belum memenuhi" (**aktif secara bawaan**)
  menampilkan mahasiswa yang memenuhi *minimal satu* syarat wajib (yang 0
  disembunyikan), tetap tanpa peringkat kedekatan. Dapat dimatikan untuk melihat
  hanya yang memenuhi seluruh syarat wajib.

---

## 2. Struktur Tabel

### 2.1 `program`

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `nama` | string | Nama program |
| `jenis` | enum(`beasiswa`,`prestasi_mahasiswa`,`lainnya`) | Jenis program |
| `deskripsi` | text nullable | |
| `penyelenggara` | string nullable | |
| `pendaftaran_mulai` / `pendaftaran_selesai` | date nullable | Periode pendaftaran |
| `kuota` | unsignedInteger nullable | Informatif — tidak memotong daftar otomatis |
| `aktif` | boolean | |
| `dibuat_oleh` | FK `pengguna` nullable | Pembuat |
| `created_at`/`updated_at`/`deleted_at` | timestamp | softDelete |

### 2.2 `program_syarat`

Satu baris = satu kriteria boolean. **Tidak ada kolom bobot/skor.**

| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | bigint PK | |
| `program_id` | FK `program` cascade | |
| `bidang` | string | Nilai enum `BidangKriteria` (mis. `ipk_rata_rata`) |
| `operator` | string | Nilai enum `OperatorKriteria` (`gte/lte/gt/lt/eq/in`) |
| `nilai` | string | Ambang; JSON untuk `in` & field khusus |
| `wajib` | boolean | Wajib ikut AND kelayakan; tidak wajib = informatif |
| `label` | string | Kalimat syarat Bahasa Indonesia untuk tampilan |

### 2.3 Tabel klasterisasi (dipakai ulang, tidak dibuat baru)

`label_klaster` bersumber dari eksekusi K-Means **terbaru**:
`klasterisasi_eksekusi` (header run) → `klasterisasi_anggota` (mahasiswa↔cluster) →
`klasterisasi_klaster.label_deskriptif`. Riwayat run lama tidak ditimpa.

### 2.4 Daftar `criterion_field` (Enum `BidangKriteria`)

| Kategori | Field | Sumber | Query-able |
|---|---|---|---|
| Akademik | `ipk_rata_rata`, `ipk_terakhir`, `tren`, `konsistensi` | Method Model Mahasiswa | tidak |
| Non-akademik | `skor_prestasi` (F5), `skor_kegiatan` (F6), `skor_pengabdian` (F7) | Rubrik SKKM | tidak |
| Non-akademik | `jumlah_prestasi_min_tingkat` | Cacah prestasi per tingkat | tidak |
| Administratif | `status`, `program_studi`, `angkatan`, `semester_aktif` | Kolom tabel mahasiswa | **ya** |
| Klasterisasi | `label_klaster` | Eksekusi K-Means terbaru | tidak |

---

## 3. Sumber Diagram (PlantUML)

### 3.1 Use Case (delta terhadap diagram existing)

```plantuml
@startuml
left to right direction
actor "Wakil Dekan III" as WD3
actor "Staf WD III" as STAF
actor "Administrator" as ADMIN
actor "Kaprodi" as KAPRODI

rectangle "Penyaringan Kandidat" {
  usecase "Kelola Program & Syarat" as UC1
  usecase "Saring Kandidat per Program" as UC2
  usecase "Lihat Status Syarat (detail)" as UC3
  usecase "Ekspor Daftar Kandidat" as UC4
  usecase "Lihat Daftar Program" as UC5
}

WD3 --> UC1
WD3 --> UC2
WD3 --> UC4
STAF --> UC1
STAF --> UC2
STAF --> UC4
ADMIN --> UC1
KAPRODI --> UC5
UC2 ..> UC3 : <<include>>
UC2 ..> UC5 : <<extend>>
@enduml
```

### 3.2 Class Diagram (`EvaluatorKelayakan` + entitas terkait)

```plantuml
@startuml
class Program {
  +nama : string
  +jenis : string
  +aktif : bool
  +syarat() : HasMany
  +syaratWajib() : HasMany
}
class ProgramSyarat {
  +bidang : string
  +operator : string
  +nilai : string
  +wajib : bool
  +label : string
  +bidangEnum() : BidangKriteria
  +operatorEnum() : OperatorKriteria
  +nilaiTerdecode() : mixed
}
enum BidangKriteria {
  +label()
  +tipe()
  +operatorValid()
  +bisaQuery()
}
enum OperatorKriteria {
  +bandingkan(aktual, ambang) : bool
}
class EvaluatorKelayakan {
  +evaluateProgram(Program, ?Collection) : Collection<HasilKelayakan>
  +evaluateStudent(Program, Mahasiswa) : HasilKriteria[]
  +kandidatQuery(Program) : Builder
}
class HasilKelayakan {
  +mahasiswa : Mahasiswa
  +layak : bool
  +kriteria : HasilKriteria[]
  +adaWajibLolos() : bool
}
class HasilKriteria {
  +bidang : string
  +label : string
  +wajib : bool
  +nilaiAktual : mixed
  +ambang : mixed
  +lolos : bool
  +keterangan : ?string
}
Program "1" *-- "0..*" ProgramSyarat
ProgramSyarat ..> BidangKriteria
ProgramSyarat ..> OperatorKriteria
EvaluatorKelayakan ..> Program
EvaluatorKelayakan ..> HasilKelayakan
HasilKelayakan "1" *-- "0..*" HasilKriteria
EvaluatorKelayakan ..> Mahasiswa
@enduml
```

### 3.3 Activity Diagram (alur penyaringan)

```plantuml
@startuml
start
:Pilih program;
if (Program punya syarat wajib?) then (tidak)
  :Tandai semua mahasiswa "layak";
  :Tampilkan peringatan;
else (ya)
  :Ambil kandidat (pushdown WHERE untuk field query-able);
  repeat :Untuk tiap mahasiswa;
    repeat :Untuk tiap syarat;
      :Ambil nilai aktual (method Mahasiswa / cacah / label klaster);
      if (data tersedia?) then (tidak)
        :kriteria = tidak lolos + keterangan;
      else (ya)
        :kriteria = operator.bandingkan(aktual, ambang);
      endif
    repeat while (syarat berikutnya?)
    :layak = AND seluruh syarat wajib;
  repeat while (mahasiswa berikutnya?)
endif
:Partisi boolean (layak / belum);
:Urutkan per SATU kolom data mentah;
:Tampilkan grid V/X + tombol detail + ekspor;
stop
@enduml
```

---

## 4. Justifikasi Metodologis (untuk penguji)

Modul ini adalah **penyaringan kelayakan boolean** (*eligibility filtering*), **bukan**
SAW/WP/TOPSIS/Profile Matching. Tiap kriteria dievaluasi **independen** sebagai
lolos/tidak; kelayakan program adalah **konjungsi (AND) seluruh syarat wajib** —
setara klausa `WHERE` bertumpuk pada basis data. **Tidak ada** pembobotan kriteria,
**tidak ada** skor gabungan/agregasi, **tidak ada** persentase kecocokan, dan
**tidak ada** pemeringkatan berdasarkan tingkat kecocokan. Pengurutan tampilan hanya
per satu kolom data mentah (mis. IPK) sebagai kenyamanan, bukan skor. Keputusan akhir
penetapan kandidat berada pada Wakil Dekan III; sistem hanya menyaring berdasarkan
persyaratan yang didefinisikan. Dengan demikian modul ini tidak memenuhi ciri sistem
pendukung keputusan MCDM maupun sistem pakar (tidak ada bobot, agregasi, maupun
inferensi berantai).

---

## 5. Permission (RBAC) & Pemetaan Peran

Ditambahkan ke daftar izin kanonik `config/peran.php` (mekanisme kustom via
`Pengguna::punyaIzin`, bukan Spatie). Otorisasi model diperkuat `App\Policies\ProgramPolicy`.

| Permission | Administrator | WD III | Staf WD III | Kaprodi | Staf Prodi |
|---|:---:|:---:|:---:|:---:|:---:|
| `program.lihat` | ✅ | ✅ | ✅ | ✅ (read-only) | ❌ |
| `program.kelola` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `program.saring` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `program.ekspor` | ✅ | ✅ | ✅ | ❌ | ❌ |

> Administrator memakai wildcard `*` sehingga otomatis memiliki seluruh izin.

---

## 6. Ringkasan Skenario Pengujian

**Pengujian fungsional (data simulasi — bukan analisis penelitian):**

*Unit `EvaluatorKelayakan` (11 kasus):* batas operator `gte`/`gt` tepat di ambang;
operator `in`; `jumlah_prestasi_min_tingkat` (tingkat ≥ target); mahasiswa belum
diklaster; IPK kosong → "data belum tersedia"; kelayakan = AND syarat wajib; syarat
opsional tidak memengaruhi; program tanpa syarat wajib → semua layak; `kandidatQuery`
mempersempit via WHERE.

*Feature CRUD program (5 kasus):* otorisasi staf_prodi ditolak & wd3 diizinkan;
membuat program + syarat; menolak kombinasi field–operator tidak valid; menyimpan
nilai JSON + label untuk operator `in`.

*Feature halaman penyaringan (5 kasus):* otorisasi; menampilkan hanya yang memenuhi
syarat wajib; audit menampilkan yang belum memenuhi; audit menyembunyikan yang 0
syarat wajib; ekspor CSV ditolak staf_prodi & berhasil untuk wd3.

Total modul: **21 pengujian lulus**. Suite proyek: **128 lulus**.

**Analisis klasterisasi (data riil):** evaluasi klaster (Silhouette/DBI/Elbow +
stabilitas/Kruskal-Wallis) tetap memakai data IPK riil F1–F4; modul penyaringan ini
tidak mengubah metodologi klasterisasi.
```
Program contoh seeder: "Beasiswa Unggulan 2026" (status aktif + IPK≥3,25 + skor
kegiatan≥40) & "Pilmapres FT 2026" (IPK terakhir≥3,50 + ≥1 prestasi nasional +
label klaster "Berprestasi" opsional).
```
