# Panduan Import Diagram SIMAFTUNSUR ke Visual Paradigm

> **Terakhir diperbarui:** 2026-07-02 — diturunkan langsung dari codebase
> (`routes/web.php`, `config/peran.php`, `app/Models/`, `app/Services/KlasterisasiService.php`,
> `ml/pipeline/`, `database/migrations/`).

## Kenapa bukan file `.vpp`?

Format proyek Visual Paradigm (`.vpp`) adalah format **biner proprietary** yang tidak
bisa dibuat di luar aplikasi VP. Jalur resmi yang didukung VP untuk membawa model
dari luar adalah **import XMI** (untuk UML) dan **Reverse DDL** (untuk ERD) — dua
jalur itulah yang dipakai file-file di folder ini. Setelah diimport sekali, simpan
sebagai `.vpp` dan seluruh editing selanjutnya dilakukan normal di VP.

## Daftar file

| File | Isi | Cara import |
|---|---|---|
| `simaftunsur-usecase.xmi` | 8 aktor + 31 use case + asosiasi/include/extend (dari routes & RBAC) | Project → Import → XMI |
| `simaftunsur-class.xmi` | 21 kelas Model + `KlasterisasiService`, atribut, operasi, 18 asosiasi bermultiplicity | Project → Import → XMI |
| `simaftunsur-activity.xmi` | 4 aktivitas (AD-01 Login, AD-02 Kelola Mahasiswa, AD-03 Impor IPK, AD-04 Klasterisasi K-Means) dengan swimlane & guard [Ya]/[Tidak] | Project → Import → XMI |
| `simaftunsur-erd.sql` | DDL MySQL 8.x — 21 tabel bisnis + seluruh FK (tanpa tabel sistem Laravel) | Tools → DB → Reverse DDL |
| `simaftunsur-activity.puml` | Sumber PlantUML keempat AD (render cepat & lampiran revisi naskah) | plantuml.com / ekstensi VS Code |

## Langkah 1 — Import XMI (Use Case, Class, Activity)

1. Buka Visual Paradigm → buat proyek baru (**File → New Project**), beri nama mis. `SIMAFTUNSUR`.
2. **Project → Import → XMI...** → pilih `simaftunsur-usecase.xmi` → OK.
3. Ulangi untuk `simaftunsur-class.xmi` dan `simaftunsur-activity.xmi`.
4. Buka **Model Explorer** (View → Panes → Model Explorer). Seluruh elemen
   (aktor, use case, kelas, simpul aktivitas) sudah ada di pohon model.

> **Catatan penting:** standar XMI hanya membawa **isi model**, bukan tata letak
> gambar. Jadi setelah import, diagram digambar dengan *drag & drop* — cepat,
> karena semua relasi ikut tergambar otomatis begitu dua elemen yang berelasi
> berada di kanvas yang sama.

### Membuat Use Case Diagram

1. Klik kanan proyek → **New Diagram → Use Case Diagram**.
2. Seret aktor dan use case dari Model Explorer ke kanvas — asosiasi,
   generalisasi aktor, «include», dan «extend» muncul sendiri.
3. Tambahkan kotak **System Boundary** dari palet, beri nama `SIMAFTUNSUR`,
   lalu masukkan semua use case ke dalamnya (aktor di luar kotak).
4. **Saran agar tidak penuh garis:** buat beberapa diagram — satu diagram
   ringkas per modul (mis. "Use Case Klasterisasi", "Use Case Data Mahasiswa")
   memakai elemen model yang sama. Di VP, satu elemen boleh tampil di banyak diagram.
5. Aktor abstrak `Pengguna` menampung use case autentikasi (Masuk, Keluar,
   Beranda, Profil); keenam peran mewarisinya lewat generalisasi sehingga
   tidak perlu 6 × 4 garis asosiasi.

### Membuat Class Diagram

1. **New Diagram → Class Diagram**, lalu seret kelas dari Model Explorer.
2. Jika asosiasi belum muncul: klik kanan kelas → **Related Elements →
   Visualize Related Model Element...** → centang relasi yang diinginkan.
3. Untuk naskah BAB III cukup tampilkan kelas fokus (ProgramStudi, Mahasiswa,
   NilaiIpkSemester, KlasterisasiEksekusi/Klaster/Anggota, KlasterisasiService,
   Pengguna) di satu diagram, dan modul pendukung di diagram kedua.

### Membuat Activity Diagram

1. **New Diagram → Activity Diagram** (satu diagram per aktivitas AD-01 … AD-04).
2. Di kanvas, buat **Vertical Swimlane** lalu ganti nama partisinya sesuai
   partisi model (mis. `Pengguna (WD III / Administrator)`,
   `SIMAFTUNSUR (Laravel)`, `Layanan Klasterisasi (Python / scikit-learn)`).
   Cara tercepat: seret partisi dari pohon model aktivitas terkait.
3. Seret simpul dari Model Explorer ke kolom swimlane-nya: InitialNode →
   aksi-aksi → DecisionNode → ActivityFinalNode. Control flow (termasuk
   guard `[Ya]`/`[Tidak]`) ikut tergambar otomatis.
4. Susun dari atas ke bawah agar sesuai gaya baku skripsi.

## Langkah 2 — Reverse DDL (ERD)

1. **Tools → DB → Reverse DDL...**
2. Pilih file `simaftunsur-erd.sql`, jenis database **MySQL** → OK.
3. Entitas dan relasi FK terbentuk di model. Buat **New Diagram →
   Entity Relationship Diagram** lalu seret entitas — garis relasi
   (dengan kardinalitas one-to-many dari FK) tergambar otomatis.
4. Saran penyajian BAB III: ERD inti (program_studi, mahasiswa,
   nilai_ipk_semester, klasterisasi_*, pengguna) di satu gambar; tabel
   modul pendukung di gambar terpisah agar terbaca.

## Standar notasi Activity Diagram (jawaban atas masukan dosen)

Diagram alur klasterisasi versi lama (di `docs/diagram-bab3.md`) digambar sebagai
*flowchart* Mermaid, bukan Activity Diagram UML — itu sebabnya terlihat asing.
Versi baru di `simaftunsur-activity.xmi`/`.puml` sudah mengikuti notasi UML baku
yang lazim dipakai di skripsi Indonesia:

| Elemen | Notasi | Di file ini |
|---|---|---|
| Titik mulai | lingkaran hitam penuh (*initial node*) | ada, 1 per diagram |
| Aktivitas | persegi panjang bersudut membulat, kata kerja aktif | ada |
| Keputusan | belah ketupat (*decision*) dengan guard `[Ya]` / `[Tidak]` | ada |
| Penggabungan | belah ketupat (*merge*) | ada |
| Titik selesai | lingkaran hitam bertepi (*activity final*) | ada |
| Swimlane | partisi vertikal `Aktor | Sistem` | ada (AD-04 tiga lajur) |

AD-04 (klasterisasi) memakai **tiga swimlane** karena tanggung jawab memang
terbagi tiga: pengguna, aplikasi Laravel, dan layanan Python. Jika pembimbing
menginginkan bentuk dua lajur saja, gabungkan lajur Laravel + Python menjadi
satu lajur `Sistem` di VP (pindahkan simpul antar-partisi dengan drag).

## Sinkronisasi dengan naskah

- Alur AD-04 mencerminkan implementasi nyata `KlasterisasiService::jalankan()`
  dan `ml/pipeline/orchestrator.py` — termasuk validasi minimum data, pemilihan
  k otomatis via Silhouette (k=2..8), evaluasi Silhouette/DBI/Inertia, dan
  peringatan bila data di bawah ambang ideal 100 mahasiswa.
- Use case *extend* tidak diberi garis aktor langsung (aksesnya mengikuti use
  case dasarnya): Reset Kata Sandi, Impor (mahasiswa/IPK), Detail Klaster,
  Ekspor Laporan, Sekolah Target, Backup Data.
- ERD hanya memuat tabel bisnis; `status_akhir` pada `mahasiswa` diberi komentar
  bahwa kolom itu disiapkan untuk pengembangan lanjutan (BAB V), bukan fitur K-Means.
