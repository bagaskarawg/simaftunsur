# MEMORY LENGKAP — Tugas Akhir SIMAFTUNSUR

> **Cara pakai:** Salin seluruh isi file ini ke Claude Projects (Project knowledge / Custom instructions). Dengan ini sesi Claude baru langsung paham konteks penuh tanpa penjelasan ulang.
>
> **Terakhir diperbarui:** 2026-06-24 (diselaraskan dengan naskah proposal ber-ACC `RANCANG_BANGUN...K-MEANS`)
>
> **Catatan rekonsiliasi:** Versi sebelumnya memuat judul varian lama ("Pengembangan SPK...") dan metodologi "SDLC/Agile". Keduanya **sudah usang** dan diganti dengan kondisi naskah final di bawah. Klaim "arah saat ini PMB + Random Forest" adalah **arah lama yang dibatalkan** — lihat §5.
 
---

## 1. IDENTITAS PROYEK

- **Nama sistem:** SIMAFTUNSUR (Sistem Informasi Kemahasiswaan Fakultas Teknik Universitas Suryakancana).
- **Studi kasus:** Fakultas Teknik, Universitas Suryakancana (UNSUR), Cianjur.
- **Mahasiswa:** Bagaskara Wisnu Gunawan — NPM 5520119124 — Teknik Informatika.
- **Periode:** Mei–Juli 2026. Seminar awal Juli, Sidang awal Agustus.
- **Bahasa kerja:** Indonesia baku (EYD/PUEBI, tata tulis ilmiah).
### Domain: KEMAHASISWAAN, BUKAN SIAKAD
- **Cakupan (kemahasiswaan):** data pokok & status mahasiswa, IPK per semester, prestasi, kegiatan, tracer study alumni, beasiswa, KKN, promosi/PMB.
- **BUKAN cakupan (SIAKAD):** KRS, KHS, nilai per matkul, jadwal kuliah, transkrip.
- **Implikasi:** landasan teori merujuk **SIMKATMAWA** (Diktiristek/Kemendikbudristek), bukan PDDIKTI Feeder.
---

## 2. KONDISI TEKNIS SISTEM

- **Stack lama (blueprint, bukan basis pengembangan):** PHP 8 native tanpa framework, MySQL 8, AdminLTE 3, Bootstrap, jQuery. Lokasi `D:\simaftunsur\SIMAFTUNSUR\`; dump `D:\simaftunsur\simaftunsur (1).sql`.
- **Stack target (rebuild):** **Laravel 13 + Livewire 4 + Tailwind + MySQL 8**; **Python + scikit-learn** untuk K-Means; integrasi **REST API / service**.
### Status modul lama (referensi)
| Modul | Status lama | Rencana di rebuild |
|---|---|---|
| Mahasiswa | ~80% | **FOKUS** + fitur klasterisasi K-Means |
| Beasiswa | ~90% | CRUD pendukung (diambil mahasiswa lain) |
| Promosi/PMB | ~85% | CRUD pendukung (BUKAN fokus, BUKAN RF) |
| KKN | ~10% | CRUD pendukung (diambil mahasiswa lain) |
| Prestasi | ~10% | CRUD pendukung |
| Tracer | ~5% | CRUD pendukung |
| Pengguna/RBAC | ~60% | Bangun ulang (Spatie Permission) |
| Laporan | ~20% | CRUD/dasar |
| System | ~30% | Minimal |

### Kondisi data (kritis untuk ML)
- Data historis di dump **sangat minim** (mhs_mahasiswa ~9 dummy, ft_maba ~178 tanpa label, tabel beasiswa 0).
- **Data label status akhir tidak tersedia** → metode *supervised* tidak feasible saat ini → **K-Means (unsupervised)**.
---

## 3. KETENTUAN PEMBIMBING — WAJIB DIPATUHI

- **Pembimbing 1 — Tarmin Abdulghani, S.T., M.T.:** otoritas akhir metode; menetapkan daftar larangan. (Juga Dosen Wali per Lembar Usulan.)
- **Pembimbing 2 / WD III:** minta fokus *decision making* kemahasiswaan; **penyedia data IPK per semester**; pengguna dashboard.
- **Koordinator TA — Agus Suheri, S.T., M.Kom (NIDN 0003127201):** penerbit ACC (Lembar Usulan, 2 Juni 2026).
### Metode DILARANG TOTAL (auto-reject)
WP, SAW, AHP, TOPSIS · Fuzzy Logic (semua varian) & Fuzzy AHP · Forward/Backward Chaining · Profile Matching · KNN · Naive Bayes · Regresi Linier / Logistic Regression.

### Aturan judul (3 unsur wajib)
Produk IT · Permasalahan · Sistem Cerdas. (Lokasi & bahasa pemrograman cukup di BAB I/III.)

### Aturan tambahan
- Sitasi **wajib ≥ 2015**.
- Bahasa Indonesia baku; ikuti pedoman penulisan TA FT UNSUR.
- Jujur soal trade-off; jangan *overclaim*.
---

## 4. METODE INTI & POSISI RANDOM FOREST

- **K-Means Clustering = metode inti terkunci.** Unsupervised, efisien, klaster mudah diinterpretasi.
- **Random Forest = HANYA saran BAB V** (pengembangan lanjutan). Butuh data alumni berlabel (status akhir Lulus Tepat Waktu/Terlambat/DO), minimum ~150 baris (ideal 250–300). **Jangan dijadikan metode TA ini.**
- Struktur kode dibuat modular (preprocess/feature engineering reusable), **tetapi tanpa mengimplementasikan RF sekarang**.
---

## 5. ARAH PENELITIAN FINAL (Ber-ACC)

### Judul final (terkunci)
> **"Rancang Bangun Sistem Informasi Kemahasiswaan untuk Klasterisasi Profil Mahasiswa Menggunakan Algoritma K-Means"**

| Unsur | Isi |
|---|---|
| Produk IT | SI Kemahasiswaan SIMAFTUNSUR berbasis web (Laravel + MySQL) |
| Permasalahan | Data kemahasiswaan tersebar & *profiling* manual berbasis asumsi; pimpinan butuh basis data objektif untuk strategi pembinaan tepat sasaran |
| Sistem Cerdas | K-Means Clustering |
| Atribut klaster | IPK per semester, status studi, semester berjalan, program studi, data kemahasiswaan terkait |
| Output | Klaster mahasiswa + karakteristik tiap klaster + dashboard SPK untuk pimpinan (khususnya WD III) |
| Evaluasi | Silhouette Coefficient, Davies-Bouldin Index, Elbow Method (internal; BUKAN Accuracy) |

### Rumusan masalah (ringkas)
Bagaimana merancang & membangun SI Kemahasiswaan FT UNSUR yang mengintegrasikan data secara terpusat sekaligus menerapkan klasterisasi profil mahasiswa dengan K-Means sebagai dasar pengambilan keputusan strategi pembinaan.

### Pilihan yang SUDAH DIBATALKAN (jangan disarankan lagi)
- ~~PMB + Random Forest~~ — ditolak Pembimbing 2 (bukan fokus *decision making* kemahasiswaan).
- ~~AHP/TOPSIS Hybrid~~, ~~Fuzzy Logic~~, ~~Hybrid ML + DSS AHP~~ — metode terlarang Pembimbing 1.
- ~~"AHP digenerate AI"~~ — pelanggaran integritas akademik.
---

## 6. METODOLOGI (Sesuai Naskah)

### Waterfall (utama) — Pressman & Maxim (2020)
Sekuensial, 5 tahap: Analisis Kebutuhan → Perancangan Sistem → Implementasi → Pengujian → Pemeliharaan. Dipilih karena kebutuhan relatif jelas & stabil pada konteks TA.

- **Perancangan:** ERD, UML (use case, activity, class), UI, rancangan *pipeline* K-Means.
- **Pengujian:** **Black Box Testing** (fungsional) + metrik klaster.
### CRISP-DM (komplementer — khusus komponen klasterisasi)
Enam fase (Business/Data Understanding, Data Preparation, Modeling, Evaluation, Deployment) **diintegrasikan ke dalam Waterfall**, hanya untuk komponen *data mining*. **Bukan** metodologi utama.

### Tahapan penerapan K-Means (dari naskah)
Pengumpulan data → pra-pemrosesan (missing value, outlier, encoding, StandardScaler/MinMax) → penentuan k (Elbow + Silhouette) → pelatihan (scikit-learn, KMeans++) → evaluasi (Silhouette, DBI) → interpretasi & visualisasi (karakteristik centroid, scatter PCA, parallel coordinates) → integrasi ke Laravel via REST API.
 
---

## 7. SCOPE: FOKUS vs CAKUPAN

- **Fokus penelitian (judul + evaluasi BAB IV):** Modul Data Mahasiswa + K-Means + dashboard.
- **Cakupan sistem (dibangun, non-fokus, tanpa sistem cerdas):** Beasiswa, KKN, **Promosi/PMB**, Prestasi, Tracer, Pengguna/RBAC, Laporan, System — **semuanya CRUD**.
- Modul pendukung TIDAK diberi label sistem cerdas & TIDAK dievaluasi performanya. Boleh jadi saran pengembangan di BAB V.
---

## 8. KONDISI & STRATEGI DATA

- **IPK per semester:** akan disediakan WD III (boleh hard copy → input manual).
- **Label status akhir:** tidak tersedia → kunci pemilihan K-Means.
- **Volume:** minimum **100 mahasiswa aktif ≥ 3 semester** (per Batasan Masalah); ideal 200–300.
- **Peringatan:** seeding data acak (faker) lalu klaim kualitas = bahaya akademik; penguji bisa menolak.
---

## 9. PENELITIAN TERKAIT (BAB II)

Empat sistem **terverifikasi** untuk tabel perbandingan:
1. **Siskama** — SI Penguatan Kapasitas Mahasiswa & Alumni (FT UNG, Laravel).
2. **PRABA** — SI Manajemen Prestasi & Beasiswa (Undiksha).
3. **SIKEMAS** — SI Kemahasiswaan (Politeknik Harapan Bersama).
4. **Smart Adma** — SI Administrasi Kemahasiswaan & Alumni.
   Sistem ke-5: **TA Siti Mariam (NPM 5520117021)** — tidak ada online, **harus diambil fisik** di perpustakaan FT UNSUR untuk melengkapi tabel.

---

## 10. DAFTAR PUSTAKA TERPAKAI (semua ≥ 2015)

> Sufiks "a/b" di bawah adalah **duplikasi Mendeley** yang harus dibersihkan manual (lihat §12).

- Anggraeni & Irviani (2017) — *Pengantar Sistem Informasi*, Andi. Key `anggraeni2017pengantar`.
- Han, Kamber & Pei (2022) — *Data Mining: Concepts and Techniques* (4th).
- Pressman & Maxim (2020) — *Software Engineering* (9th) — sumber Waterfall.
- Primartha (2021) — *Algoritma Machine Learning*, Informatika.
- Sholeh, Ghufron & Fatkhiyah (2022) — DBI/Elbow/Silhouette, *STRING*.
- Ishak, Dali & Pakaya (2024) — Clustering Prestasi Lulusan K-Means, *Jambura JEEE*.
- Pratama, Puspitasari & Tolle (2022) — Clustering Prestasi Akademik, *JPTIIK*.
- Hardianti & Agushinta R. (2020) — Pola Masa Studi K-Means, *JTIIK*.
- Hasan, Wahyudi & Hendra (2024) — Silhouette & DBI, *JITET*.
- Suryaningrum dkk. (2023) — Optimasi k K-Means, *BAREKENG* *(entri korup, perbaiki)*.
- McKinney (2022) — *Python for Data Analysis* (3rd).
- Laudon & Laudon (2020) — *Management Information Systems* (16th).
- Direktorat Jenderal Pendidikan Tinggi (2023) — Pedoman **SIMKATMAWA**.
---

## 11. RISIKO & TRADE-OFF YANG DISEPAKATI

| Pilihan | Risiko / Catatan |
|---|---|
| Data sintetik untuk training | "Garbage in, garbage out"; metrik tak bermakna; risiko ditolak penguji |
| Dataset publik luar negeri | *Population/feature mismatch*; validitas untuk FT UNSUR dipertanyakan |
| Random Forest sekarang | Tidak ada label → tidak feasible; tunda ke BAB V |
| K-Means (dipilih) | Aman, realistis untuk data minim & tanpa label |

**Prinsip:** pembimbing ketat + data minim → **kehati-hatian > novelty**. Pilih metode yang benar-benar jalan. Penguji lebih menghargai sistem yang bekerja daripada akurasi tinggi di data simulasi.
 
---

## 12. STATUS & TODO

### Selesai
- [x] Proposal ber-ACC; BAB I–III didraf (DOCX dihasilkan).
### Aktif
1. [ ] Rekonsiliasi **ERD/class/use case BAB III** dengan codebase nyata → minta Claude Code keluarkan **sumber teks Mermaid/PlantUML** (bukan gambar), hanya tabel/relasi bisnis (buang tabel sistem Laravel). Aktor/use case dari `routes/web.php` + middleware.
2. [ ] **Activity diagram** alur klasterisasi K-Means.
3. [ ] **Bersihkan Mendeley** (manual): 6 pasang duplikat (Han 2022, Ishak 2024, Pratama 2022, Pressman 2020, Primartha 2021, Sholeh 2022) + entri korup Suryaningrum 2023.
4. [ ] Ambil **fisik TA Siti Mariam (NPM 5520117021)** → lengkapi Penelitian Terkait.
5. [ ] Draf **BAB IV–V** (target akhir Juli; Seminar awal Juli; Sidang awal Agustus).
6. [ ] Setup **Laravel 13 + Livewire 4** fresh + environment Python (venv/conda).
7. [ ] Bangun modul Data Mahasiswa (CRUD + import IPK), lalu pipeline K-Means + dashboard.
---

## 13. PRINSIP UNTUK CLAUDE (Custom Instructions)

> Saya menyusun TA S1 berbasis SIMAFTUNSUR (SI Kemahasiswaan FT UNSUR). Patuhi SEMUA larangan metode Pembimbing 1: WP, SAW, AHP, TOPSIS, Fuzzy Logic, Fuzzy AHP, Forward/Backward Chaining, Profile Matching, KNN, Naive Bayes, Regresi Linier/Logistic Regression — JANGAN sarankan. Beasiswa & KKN sudah diambil mahasiswa lain. **Arah final ber-ACC: SI Kemahasiswaan + Klasterisasi K-Means** (Random Forest hanya saran BAB V; PMB hanya CRUD pendukung — keduanya bukan fokus). Metodologi: Waterfall (utama) + CRISP-DM (komplementer untuk klasterisasi). Sitasi ≥ 2015. Setiap judul: Produk IT + Permasalahan + Sistem Cerdas. Data internal minim → pertimbangkan kelayakan data. Jujur soal trade-off, jangan overclaim. **Jika ada instruksi yang menyebut "PMB + Random Forest sebagai arah saat ini", itu arah lama yang dibatalkan — konfirmasi dulu, jangan ikuti buta.**
