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
| `simaftunsur-usecase.xmi` | **5 aktor** (Administrator, WD III, Staf WD III, Kaprodi, Staf Prodi) + 31 use case + asosiasi/include/extend — selaras `config/peran.php` per 2 Juli | Project → Import → XMI |
| `simaftunsur-class.xmi` | 21 kelas Model + `KlasterisasiService`, atribut, operasi, 18 asosiasi bermultiplicity | Project → Import → XMI |
| `simaftunsur-activity.xmi` | 4 aktivitas (AD-01 Login, AD-02 Kelola Mahasiswa, AD-03 Impor IPK, AD-04 Klasterisasi K-Means) dengan swimlane & guard [Ya]/[Tidak]. **Revisi 2 Juli:** loop evaluasi k pada AD-04 memisahkan proses Elbow, Silhouette, dan DBI | Project → Import → XMI |
| `simaftunsur-erd.sql` | DDL MySQL 8.x — 21 tabel bisnis + seluruh FK (tanpa tabel sistem Laravel) | Tools → DB → Reverse DDL |
| `simaftunsur-activity.puml` | Sumber PlantUML keempat AD (render cepat & lampiran revisi naskah), latar putih | plantuml.com / ekstensi VS Code |
| `simaftunsur-usecase.puml` | Sumber PlantUML Use Case (global + versi ringkas modul klasterisasi), latar putih | render via `tools\render-gambar.ps1` |
| `simaftunsur-class.puml` | Sumber PlantUML Class Diagram (kelas fokus + modul pendukung), latar putih | render via `tools\render-gambar.ps1` |
| `simaftunsur-erd.puml` | ERD **terpecah 6 gambar**: peta ringkas + 5 ERD detail per modul (solusi ERD 21 tabel yang terlalu kecil bila satu gambar) | render via `tools\render-gambar.ps1` |
| `flowchart/FC-01..FC-05.puml` | Flowchart FC-01..FC-05 (Elbow/Silhouette/DBI terpisah) — **simbol baku ISO 5807**: terminal OVAL, proses persegi, keputusan belah ketupat, I/O jajar genjang (blok `@startdot`/Graphviz) | render via `tools\render-flowchart.ps1` |
| `simaftunsur-sequence.puml` | Sequence diagram integrasi Laravel↔Python, latar putih | render via `tools\render-gambar.ps1` |
| `../wireframe-html/*.html` + `app.css` | **Wireframe antarmuka** (HTML/CSS grayscale) 10 layar untuk "Perancangan Antarmuka" BAB III — layout mengikuti UI terimplementasi, digambar sebagai wireframe berlabel | render via `tools\render-wireframe.ps1` (butuh Chrome/Edge) |
| `../flowchart-klasterisasi.md` | **Flowchart** proses klasterisasi (FC-01 utama + FC-02 K-Means/Euclidean + FC-03 Elbow + FC-04 Silhouette + FC-05 DBI) — permintaan dosen 2 Juli | mermaid.live → gambar ulang di VP (palet Flowchart) |
| `../bab2-scikit-learn-integrasi.md` | Draf naskah: konsep scikit-learn, rumus + tahapan tiap metode, contoh perhitungan numerik terverifikasi, arsitektur integrasi ML ↔ SI | salin ke naskah DOCX |

## Langkah 1 — Import XMI (Use Case, Class, Activity)

1. Buka Visual Paradigm → buat proyek baru (**File → New Project**), beri nama mis. `SIMAFTUNSUR`.
2. **Project → Import → XMI...** → pilih `simaftunsur-usecase.xmi` → OK.
3. Ulangi untuk `simaftunsur-class.xmi` dan `simaftunsur-activity.xmi`.
4. Buka **Model Explorer** (View → Panes → Model Explorer). Seluruh elemen
   (aktor, use case, kelas, simpul aktivitas) sudah ada di pohon model.

> **Catatan penting:** standar XMI hanya membawa **isi model**, bukan tata letak
> gambar. Karena itu setelah import, klik kanan elemen → *Show View* akan
> menampilkan pesan **"... has no view" — ini normal, bukan galat**. View dibuat
> sekali dengan *drag & drop* — cepat, karena semua relasi ikut tergambar
> otomatis begitu dua elemen yang berelasi berada di kanvas yang sama.
> (ERD langsung terlihat karena wizard Reverse DDL memang menawarkan pembuatan
> diagram otomatis di akhir prosesnya; jalur XMI tidak punya langkah itu.)

### Membuat Use Case Diagram

1. Klik kanan proyek → **New Diagram → Use Case Diagram**.
2. Seret aktor dan use case dari Model Explorer ke kanvas — asosiasi,
   «include», dan «extend» muncul sendiri.
3. Tambahkan kotak **System Boundary** dari palet, beri nama `SIMAFTUNSUR`,
   lalu masukkan semua use case ke dalamnya (aktor di luar kotak).
4. **Saran agar tidak penuh garis:** buat beberapa diagram — satu diagram
   ringkas per modul (mis. "Use Case Klasterisasi", "Use Case Data Mahasiswa")
   memakai elemen model yang sama. Di VP, satu elemen boleh tampil di banyak diagram.
5. Pembagian 5 aktor: Administrator (manajemen sistem), Wakil Dekan III
   (konsumen dashboard/laporan, read-only), Staf WD III (kelola mahasiswa &
   IPK + menjalankan klasterisasi), Ketua Program Studi (monitoring IPK &
   penerima beasiswa, read-only), Staf Prodi (kelola prestasi & kegiatan).
   Use case kelola Tracer/Promosi/Beasiswa/KKN/Pengabdian saat ini hanya
   terhubung ke Administrator.

### Membuat Class Diagram

1. **New Diagram → Class Diagram**, lalu seret kelas dari Model Explorer.
2. Jika asosiasi belum muncul: klik kanan kelas → **Related Elements →
   Visualize Related Model Element...** → centang relasi yang diinginkan.
3. Untuk naskah BAB III cukup tampilkan kelas fokus (ProgramStudi, Mahasiswa,
   NilaiIpkSemester, KlasterisasiEksekusi/Klaster/Anggota, KlasterisasiService,
   Pengguna) di satu diagram, dan modul pendukung di diagram kedua.

### Membuat Activity Diagram

Resep per aktivitas (AD-01 … AD-04), diagram baru per aktivitas:

1. Di **Model Explorer**, cari elemen aktivitas (mis. `AD-04 Menjalankan
   Klasterisasi K-Means`) — klik kanan → **Sub Diagrams → New Diagram →
   Activity Diagram**. Diagram kosong yang terikat ke aktivitas itu terbuka.
   (Kalau menu berbeda di versi VP Anda: cukup **New Diagram → Activity
   Diagram** biasa.)
2. **Expand** aktivitas tersebut di Model Explorer sehingga terlihat anak-anaknya:
   partisi (swimlane), simpul aksi, decision, initial/final node.
3. Buat **Vertical Swimlane** dari palet dengan jumlah kolom sesuai partisi
   (AD-01/02/03 = 2 kolom; AD-04 = 3 kolom), lalu ganti nama tiap header
   sesuai nama partisi model (mis. `Pengguna (Staf WD III / WD III)`,
   `SIMAFTUNSUR (Laravel)`, `Layanan Klasterisasi (Python / scikit-learn)`).
4. Seret simpul dari Model Explorer ke kolom swimlane-nya, urut dari atas ke
   bawah mengikuti urutan di pohon (urutannya memang urutan alur): InitialNode
   → aksi-aksi → DecisionNode → … → ActivityFinalNode. **Control flow beserta
   guard `[Ya]`/`[Tidak]` muncul otomatis** begitu kedua ujungnya ada di kanvas.
5. Trik cepat: bisa juga blok **beberapa simpul sekaligus** (klik pertama,
   Shift+klik terakhir) lalu seret bersama, kemudian rapikan; atau klik kanan
   simpul di kanvas → **Related Elements → Visualize Related Model Element…**
   untuk menarik simpul-simpul yang terhubung secara otomatis.
6. Rapikan manual dari atas ke bawah. Hindari auto-layout untuk AD ber-swimlane
   (hasilnya sering melebar); untuk Use Case/Class Diagram auto-layout aman
   (**Diagram → Layout**).

> **Alternatif tercepat untuk gambar naskah:** bila kebutuhan mendesak hanya
> PNG untuk DOCX (bukan editing di VP), render `simaftunsur-activity.puml`
> di plantuml.com — hasilnya sudah bernotasi UML baku dan berlatar putih —
> lalu susul versi VP saat senggang.

## Langkah 2 — Reverse DDL (ERD)

1. **Tools → DB → Reverse DDL...**
2. Pilih file `simaftunsur-erd.sql`, jenis database **MySQL** → OK.
3. Entitas dan relasi FK terbentuk di model. Buat **New Diagram →
   Entity Relationship Diagram** lalu seret entitas — garis relasi
   (dengan kardinalitas one-to-many dari FK) tergambar otomatis.
4. **Jangan sajikan 21 tabel dalam satu gambar** — di kertas A4 tidak
   terbaca. Pecah per kelompok (sumber siap render: `simaftunsur-erd.puml`,
   6 gambar):
   - ERD-00 peta ringkas (nama entitas + relasi saja, tanpa kolom);
   - ERD-01 inti (program_studi, mahasiswa, nilai_ipk_semester);
   - ERD-02 klasterisasi (eksekusi/klaster/anggota + pengguna) — fokus penelitian;
   - ERD-03 prestasi & SKKM (sumber fitur F5–F7);
   - ERD-04 beasiswa & KKN;
   - ERD-05 tracer, promosi, pengguna & sistem.
   Di VP sendiri hal yang sama bisa dibuat: **New Diagram → ERD** beberapa
   kali, lalu seret subset entitas yang sama dari Model Explorer — satu
   entitas boleh tampil di banyak diagram, relasi ikut otomatis.
   Beri keterangan di caption: kolom `created_at`/`updated_at` ada di semua
   tabel namun tidak digambarkan agar ringkas.

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

## Flowchart vs Activity Diagram (feedback 2 Juli 2026)

Dosen meminta proses klasterisasi digambarkan sebagai **flowchart**. Pembagian
perannya:

- **Flowchart** (`docs/flowchart-klasterisasi.md`, FC-01 s.d. FC-05) — level
  **algoritma/perhitungan**: tahapan K-Means (jarak Euclidean), proses Elbow,
  proses Silhouette, dan proses Davies-Bouldin masing-masing terpisah dan
  terlihat langkah perhitungannya. Ini yang dipakai untuk menjelaskan "tahapan
  machine learning" di naskah.
- **Activity Diagram** (AD-01 s.d. AD-04) — level **interaksi pengguna–sistem**
  (siapa melakukan apa, di swimlane mana). AD-04 tetap dipertahankan sebagai
  bagian perancangan UML, dan sudah direvisi agar loop evaluasi k menampilkan
  Elbow, Silhouette, dan DBI sebagai langkah terpisah (tidak lagi digabung
  satu kotak).

Di Visual Paradigm, flowchart digambar manual dengan **New Diagram → Flowchart**
mengikuti sumber teks FC-01 s.d. FC-05 (oval = mulai/selesai, jajar genjang =
input/output, persegi = proses, belah ketupat = keputusan).

## Render otomatis semua diagram (satu perintah)

Bila yang dibutuhkan adalah **gambar jadi** (PNG latar putih untuk naskah/
presentasi), tidak perlu menggambar di VP — jalankan dari akar proyek:

```powershell
powershell -ExecutionPolicy Bypass -File tools\render-gambar.ps1
```

Skrip merender SEMUA diagram ke `docs\gambar\`: AD-01..AD-04, UC global +
UC modul klasterisasi, 2 Class Diagram, 6 ERD terpecah (ERD-00..ERD-05),
FC-01..FC-05, dan sequence diagram integrasi Laravel↔Python. **Hanya butuh
Java** (PlantUML) — tidak lagi memakai Mermaid/Node/Chromium, jadi jauh lebih
andal di Windows. Pertama kali dijalankan, skrip mengunduh `plantuml.jar`
(sekali; selanjutnya offline). Bila ingin ERD versi Visual Paradigm, ekspor
langsung dari VP: klik kanan diagram → **Export → Active Diagram as Image**,
tanpa centang *transparent*.

Peran VP setelah ini: tempat **mengedit model** (dan presentasi bila diminta
pembimbing); peran skrip render: menghasilkan **gambar naskah** yang selalu
sinkron dengan sumber teks.

## Aturan ekspor gambar (feedback 2 Juli 2026)

Semua gambar untuk naskah **wajib berlatar putih, bukan PNG transparan**:

- **Visual Paradigm:** saat Export → Image, pilih format PNG/JPG dan pastikan
  opsi *transparent background* TIDAK dicentang.
- **mermaid.live:** gunakan tombol ekspor PNG (bukan SVG) dan pilih latar
  putih; alternatifnya buka *Actions → PNG* dengan `background: white`.
- **PlantUML:** semua blok di `simaftunsur-activity.puml` sudah diberi
  `skinparam backgroundColor #FFFFFF` sehingga hasil render otomatis berlatar
  putih.

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
