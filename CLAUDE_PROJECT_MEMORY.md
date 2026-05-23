# MEMORY LENGKAP — Tugas Akhir SIMAFTUNSUR

> **Cara pakai:** Salin seluruh isi file ini ke dalam Claude Projects (bagian "Project knowledge" atau "Custom instructions"). Dengan ini, sesi Claude baru (termasuk dari HP) langsung paham konteks penuh tanpa perlu menjelaskan ulang.
>
> **Terakhir diperbarui:** 2026-05-22

---

## 1. IDENTITAS PROYEK

- **Nama sistem:** SIMAFTUNSUR (Sistem Informasi Kemahasiswaan Fakultas Teknik Universitas Suryakancana)
- **Lokasi studi kasus:** Fakultas Teknik, Universitas Suryakancana (UNSUR), Cianjur
- **Konteks:** Sistem ini adalah objek/wadah untuk Tugas Akhir (Skripsi) S1
- **Periode pengajuan proposal:** Mei 2026
- **Bahasa kerja:** Indonesia (baku, sesuai EYD/PUEBI dan tata tulis laporan ilmiah)

### PENTING — Domain Sistem

SIMAFTUNSUR adalah **Sistem Informasi KEMAHASISWAAN (student affairs)**, **BUKAN** Sistem Informasi Akademik (SIAKAD).

- **Cakupan (kemahasiswaan):** Beasiswa, KKN, Prestasi, Tracer Study alumni, Promosi/PMB, data pokok mahasiswa & status (aktif/cuti/lulus/DO/pindah)
- **BUKAN cakupan (SIAKAD):** KRS, KHS, nilai per matkul, jadwal kuliah, perwalian akademik, transkrip

Implikasi: judul TA harus pakai istilah "kemahasiswaan", landasan teori merujuk **SIMKATMAWA** (regulasi Diktiristek), bukan SIAKAD/PDDIKTI Feeder.

---

## 2. KONDISI TEKNIS SISTEM SAAT INI

- **Stack lama:** Custom PHP 8.x tanpa framework, MySQL 8.x, AdminLTE 3, Bootstrap, jQuery, session native PHP, bcrypt
- **Stack target (rebuild):** Laravel (rencana Laravel 13 + Livewire 4)
- **Lokasi kode:** `D:\simaftunsur\SIMAFTUNSUR\`
- **File SQL dump:** `D:\simaftunsur\simaftunsur (1).sql`
- **Autentikasi:** dua tabel — `users` (SDM/Dosen) & `user_mahasiswa` (mahasiswa, role_id=99)
- **RBAC:** tabel `roles`, `menus`, `submenus`, `role_submenu`; ada tool auto-sync `tools/sync_modules_to_db.php`

### Struktur Folder

```
SIMAFTUNSUR/
├── config/config.php          # konfigurasi DB + BASE_URL (kredensial hosting masih hardcoded!)
├── dashboard/                 # dashboard RBAC dinamis
├── layout/                    # header, sidebar, topbar, footer
├── modules/                   # 10 modul, 79 file PHP
│   ├── beasiswa/   (15 file, ~90% jadi)
│   ├── mahasiswa/  (14 file, ~80% jadi)
│   ├── kkn/        (11 file, ~90% PLACEHOLDER)
│   ├── prestasi/   (9 file, ~90% PLACEHOLDER)
│   ├── promosi/    (12 file, ~85% jadi)
│   ├── tracer/     (2 file, PLACEHOLDER)
│   ├── laporan/    (7 file, ~60% placeholder)
│   ├── pengguna/   (3 file, roles.php placeholder)
│   ├── ftunsur/    (2 file: import MABA + SDM, jadi)
│   └── system/     (5 file, mayoritas placeholder)
├── tools/sync_modules_to_db.php
├── auth.php, login.php, logout.php, index.php
└── docs/                      # dokumentasi + file memory ini
```

### Status Modul (Real vs Placeholder)

| Modul | Status | Catatan |
|---|---|---|
| Mahasiswa | ~80% jadi | CRUD, profil, rekap angkatan/prodi sudah ada |
| Beasiswa | ~90% jadi | Modul terlengkap (kategori, pengajuan, penerima, monitoring IPK) |
| Promosi/PMB | ~85% jadi | Petugas, jadwal kunjungan, sekolah, kwitansi, disposisi |
| FT UNSUR | Jadi | Import MABA, SDM |
| Pengguna | ~60% | users & hak_akses jadi; roles.php placeholder |
| KKN | ~10% | Mayoritas placeholder |
| Prestasi | ~10% | Mayoritas placeholder |
| Tracer | ~5% | survey.php & laporan.php hampir kosong |
| Laporan | ~20% | Sebagian besar placeholder |
| System | ~30% | Mayoritas placeholder skeleton |

### Kondisi Data (KRITIS untuk ML)

Data historis di SQL dump **sangat minim**:

| Tabel | Jumlah Baris |
|---|---|
| `bea_calon` (calon beasiswa) | 0 |
| `bea_penerima` | 0 |
| `bea_nilai` | 0 |
| `mhs_mahasiswa` | 9 (dummy) |
| `mhs_penerima_bantuan` | 2 |
| `ft_maba` (mahasiswa baru) | ~178 (data asli, tapi belum berlabel) |

**Kesimpulan:** Data internal untuk training ML praktis nol. Ini constraint utama dalam memilih metode.

---

## 3. KETENTUAN PEMBIMBING TA (PERIODE MEI 2026) — WAJIB DIPATUHI

### Konfigurasi Pembimbing

- **Pembimbing 1:** Ketat soal metode; menetapkan daftar metode terlarang (lihat di bawah). Otoritas akhir untuk pemilihan algoritma.
- **Pembimbing 2 (Wakil Dekan III / WD III):** Mengurus beasiswa & kemahasiswaan secara umum. Fokus pada **decision making + kemahasiswaan luas**. TIDAK setuju fokus PMB. Mengusulkan AHP/TOPSIS dan Fuzzy Logic — tapi semua TERLARANG oleh Pembimbing 1. Berkomitmen menyediakan **data IPK per semester** (boleh hard copy).
- **Strategi negosiasi:** Patuhi Pembimbing 1 untuk metode, akomodasi Pembimbing 2 untuk arah & cakupan. Konsep "SPK" tetap dipertahankan tapi mesin pendukungnya pakai algoritma yang diizinkan (K-Means).

### Metode DILARANG TOTAL (auto-reject)

- MCDM klasik: **WP, SAW, AHP, TOPSIS**
- Sistem Pakar: **Forward Chaining, Backward Chaining**
- **Fuzzy Logic** (semua varian: Tsukamoto, Mamdani, Sugeno)
- **KNN** (K-Nearest Neighbors)
- **Naive Bayes**
- **Regresi Linier**

### Topik DIBATASI (siapa daftar duluan dia dapat)

- Topik **Sistem Pakar**
- Topik **Chatbot**
- Topik **Sistem Informasi (SI)** umum

### Permasalahan/Tempat DILARANG

- **Stock Gym**
- **Sekolah** (sebagai tempat penelitian) — karena dianggap bisa selesai tanpa sistem cerdas / cukup SQL

### Topik SUDAH DIAMBIL mahasiswa lain (tidak boleh dipakai)

- **Beasiswa** (sudah diambil — konsultasi 2026-05-21)
- **KKN** (sudah diambil)

### Aturan Judul (3 unsur WAJIB muncul)

1. **Produk IT**
2. **Permasalahan**
3. **Sistem Cerdas** yang digunakan

(Lokasi penelitian & bahasa pemrograman tidak wajib di judul, cukup di Bab I/III)

### Aturan Tambahan

- Jangan pakai kata "Rancang Bangun" (sudah jenuh) — pakai "Pengembangan", "Penerapan", "Implementasi", dll.
- Untuk **Sistem Prediksi**: harus jelas/spesifik APA yang diprediksi.
- Bahasa Indonesia baku, sesuai tata tulis laporan ilmiah.
- Ikuti template & pedoman penulisan TA kampus.
- Proposal ditolak → ganti judul; direvisi → perbaiki.
- Daftar pengumpulan proposal harus + kirim GForm (jangan asal daftar).
- Topik SI dibatasi → daftar cepat.

---

## 4. METODE YANG MASIH AMAN (Boleh Dipakai)

| Metode | Tipe | Kegunaan untuk SIMAFTUNSUR |
|---|---|---|
| **Random Forest** | Klasifikasi/Prediksi ensemble | Klasifikasi/prediksi, butuh ~200+ baris berlabel |
| **Decision Tree C4.5** | Klasifikasi | Explainable, toleran data kecil (~100 baris) |
| **Gradient Boosting / XGBoost** | Klasifikasi/Prediksi ensemble | Akurasi tinggi, butuh ~300-500 baris, agak fussy tuning |
| **Content-Based Filtering** (TF-IDF + Cosine Similarity) | Rekomendasi | TIDAK butuh data berlabel — paling aman untuk data minim |
| **Case-Based Reasoning (CBR)** | Rekomendasi berbasis kasus | Butuh arsip kasus |
| **K-Means / DBSCAN** | Clustering | Segmentasi profil, tidak perlu label |
| **Apriori / FP-Growth** | Association rule | Pencarian pola |
| **Genetic Algorithm** | Optimasi | Optimasi penempatan/rute (mis. KKN, rute kunjungan PMB) |
| **SVM** | Klasifikasi | Klasifikasi biner |

---

## 5. ARAH PENELITIAN SAAT INI (Keputusan Final per 2026-05-22)

### JUDUL FINAL (Sudah Dikunci, Tinggal Konsultasi Persetujuan)

**Judul Utama:**
> **"Pengembangan Sistem Pendukung Keputusan Kemahasiswaan dengan Segmentasi Profil Mahasiswa Menggunakan Algoritma K-Means Clustering"**

**Varian alternatif jika pembimbing minta revisi:**
- "Pengembangan Sistem Pendukung Keputusan Kemahasiswaan dengan Pengelompokan Mahasiswa Berdasarkan Profil Akademik Menggunakan Algoritma K-Means Clustering"
- "Pengembangan Sistem Pendukung Keputusan Strategi Pembinaan Mahasiswa dengan Pengelompokan Profil Akademik Menggunakan Algoritma K-Means Clustering"

| Unsur | Isi |
|---|---|
| Produk IT | Sistem Pendukung Keputusan Kemahasiswaan (modul terintegrasi di SIMAFTUNSUR) |
| Permasalahan | WD III memerlukan segmentasi profil mahasiswa untuk merumuskan strategi pembinaan yang tepat sasaran, namun saat ini dilakukan manual/berbasis asumsi tanpa basis data sistematis |
| Sistem Cerdas | **K-Means Clustering** (unsupervised, tidak butuh data berlabel) |
| Output sistem | Cluster mahasiswa + karakteristik tiap cluster + rekomendasi strategi pembinaan + dashboard SPK untuk pimpinan |
| Data | IPK per semester (akan disediakan WD III) + status mahasiswa + semester + prodi + data kemahasiswaan |
| Evaluasi | Silhouette Score, Davies-Bouldin Index, Elbow Method (BUKAN Accuracy/Precision karena unsupervised) |

### KONTEKS PENGAMBILAN KEPUTUSAN (PENTING — Jangan Diubah)

Keputusan K-Means diambil setelah analisis matang dengan trade-off berikut:

1. **Awalnya** user menyusun judul PMB + Random Forest (data internal lengkap, jarang diambil)
2. **Pembimbing 2 (WD III)** tidak setuju fokus PMB; ingin pembahasan **decision making + kemahasiswaan luas** (sesuai jabatannya)
3. **Pembimbing 2** memberikan dokumen usulan dengan 7 alternatif, namun **mayoritas memakai metode terlarang Pembimbing 1** (AHP-TOPSIS, Fuzzy Logic, Naive Bayes)
4. **WD III berkomitmen** menyediakan data IPK per semester (akan dimasukkan ke menu "Data Mahasiswa"), boleh dalam bentuk hard copy
5. **Data label/status akhir** untuk supervised learning **TIDAK TERSEDIA** — perlu data alumni historis yang berat untuk dikumpulkan
6. **Solusi:** Pakai K-Means (unsupervised, tidak butuh label) → memuaskan Pembimbing 2 (ada di opsi #6 dokumennya) + aman dari larangan Pembimbing 1 + realistis dengan data tersedia

### Rencana Pengembangan Lanjutan (Untuk Bab V Saran TA)

**Random Forest untuk klasifikasi risiko mahasiswa** akan menjadi penelitian lanjutan pasca-TA. Memerlukan:
- Data alumni historis (angkatan 2015-2019) dengan label status akhir (Lulus Tepat Waktu / Terlambat / DO)
- Minimum 150 baris (marjinal), ideal 250-300 baris
- Implementasi teknis ~50-60% reusable dari kode K-Means (modular: preprocessing & feature engineering bisa dipakai ulang)
- Beban berat ada di pengumpulan data label + perubahan metodologi (Bab III) + evaluasi (metrik beda total)

### Aturan Penting untuk Implementasi Kode

**Struktur kode harus modular** agar migrasi ke RF di masa depan mudah:

```python
def preprocess(data): ...           # reusable untuk RF
def feature_engineering(df): ...    # reusable untuk RF  
def train_kmeans(X): ...            # ganti jadi train_random_forest(X, y) saat migrasi
def evaluate(model, X): ...         # ganti metrik saat migrasi
```

Database harus menyiapkan kolom untuk label (status akhir mahasiswa) walau belum digunakan sekarang.

### Pilihan-Pilihan yang SUDAH DIBATALKAN (Jangan Disarankan Lagi)

- ~~PMB + Random Forest~~ — ditolak Pembimbing 2 (bukan fokus decision making kemahasiswaan)
- ~~AHP/TOPSIS Hybrid~~ — dilarang Pembimbing 1 (metode terlarang)
- ~~Fuzzy Logic~~ — dilarang Pembimbing 1
- ~~Hybrid ML + DSS dengan AHP~~ — kombinasi metode terlarang
- ~~"AHP digenerate ChatGPT"~~ — pelanggaran integritas akademik, AHP butuh expert judgment manusia, BUKAN AI

---

## 6. KONSEP CAKUPAN SISTEM vs FOKUS PENELITIAN

Prinsip penting: **lingkup pengembangan boleh lebih luas dari lingkup penelitian**.

- **Fokus penelitian (masuk judul + dievaluasi ilmiah):** HANYA satu modul + sistem cerdasnya (saat ini: **SPK Kemahasiswaan / Modul Data Mahasiswa + K-Means Clustering**)
- **Cakupan sistem (dibangun + dipakai produksi):** SELURUH modul SIMAFTUNSUR (Mahasiswa+smart, Beasiswa-CRUD, KKN-CRUD, Prestasi, Tracer, Promosi, Pengguna, Laporan)
- **Modul pendukung** diposisikan sebagai "modul pengelolaan data" — TIDAK diberi label sistem cerdas, TIDAK dievaluasi.

### Aturan posisi modul lain

- ❌ Jangan cantumkan modul lain di JUDUL TA
- ❌ Jangan klaim sistem cerdas untuk modul pendukung
- ❌ Jangan evaluasi performa smart system modul pendukung
- ✅ Modul lain boleh jadi "saran pengembangan lanjutan" di Bab V

### Catatan kolaborasi (perlu klarifikasi ke pembimbing)

Karena beasiswa & KKN diambil mahasiswa lain — perlu dipastikan: apakah mereka mengembangkan SIMAFTUNSUR yang SAMA atau aplikasi TERPISAH?
- Jika SAMA → jangan bangun ulang modul beasiswa/KKN, itu pekerjaan mereka; fokus SPK Kemahasiswaan + integrasi
- Jika TERPISAH → boleh include modul beasiswa/KKN sebagai CRUD biasa di SIMAFTUNSUR Anda

### Catatan tentang Modul Promosi PMB (pivot history)

Modul PMB awalnya jadi fokus penelitian (dengan Random Forest), tapi **DIBATALKAN** karena Pembimbing 2 (WD III) menolak fokus PMB dan minta fokus decision making kemahasiswaan. Modul PMB tetap dibangun sebagai bagian sistem (CRUD biasa) tapi BUKAN fokus penelitian dan TIDAK pakai sistem cerdas.

---

## 7. STRATEGI DATA UNTUK ML

### Update per 2026-05-22

Status data terbaru:
- **IPK per semester:** ✅ WILL BE AVAILABLE — WD III berkomitmen menyediakan (boleh hard copy → input manual)
- **Data label/status akhir:** ❌ TIDAK TERSEDIA (kalau perlu, harus dari arsip alumni — beban berat)
- **Karena label tidak ada** → metode supervised (Random Forest, Decision Tree, dll.) tidak feasible saat ini
- **Solusi yang dipilih:** K-Means Clustering (unsupervised, tidak butuh label)

### Target Volume Data

- **Minimum yang masih bisa diterima:** 100-150 mahasiswa (cluster bisa kurang stabil)
- **Ideal:** 200-300 mahasiswa dengan IPK per semester lengkap
- **Periode mahasiswa:** sebaiknya mahasiswa **aktif semester 3+** (sudah ada riwayat IPK 2-3 semester untuk membentuk profil)

### Opsi Strategi Data (untuk metode yang butuh training, jika di masa depan migrasi ke supervised)

1. **Minta data historis nyata** ke Wakil Dekan III / BAAK (paling ideal, bahkan 200-500 baris cukup)
2. **Dataset publik** (lihat bagian 8) + adaptasi domain + disclaimer di Bab III
3. **Data sintetik berbasis distribusi referensi** (risiko tinggi, harus dilaporkan eksplisit di metodologi, JANGAN untuk pamer akurasi)
4. **Tetap dengan metode unsupervised** seperti K-Means (paling aman untuk kondisi data sekarang)

**Peringatan:** Seeding data acak via faker untuk training ML lalu klaim akurasi tinggi = bahaya akademik, penguji bisa menolak.

---

## 8. DATASET PUBLIK YANG SUDAH DIVERIFIKASI

### Untuk topik student/dropout/scholarship (umum)

- **UCI: Predict Students' Dropout and Academic Success** (Realinho et al., 2021) — 4.424 baris, 36 fitur, 3 kelas (Dropout/Enrolled/Graduate), CC BY 4.0, DOI 10.24432/C5MC89. URL: archive.ics.uci.edu/dataset/697
- **UCI: Higher Education Students Performance Evaluation** (2019) — 145 baris, 31 fitur, ada kolom "Scholarship type". DOI 10.24432/C51G82. URL: archive.ics.uci.edu/dataset/856
- **Kaggle mirror:** kaggle.com/datasets/thedevastator/higher-education-predictors-of-student-retention
- **data.go.id KIP Kuliah 2023** — agregat (bukan per mahasiswa), untuk validasi distribusi / argumen Bab I

### Untuk topik PMB / school recruitment

Belum ada dataset publik Indonesia yang langsung cocok — kemungkinan harus pakai data internal FT UNSUR (`ft_sekolah` + `ft_maba` + histori kunjungan) atau bangun dataset sendiri dari arsip promosi. Ini perlu dieksplorasi lebih lanjut.

---

## 9. REFERENSI KUNCI (untuk Bab II & Daftar Pustaka)

### Sistem Informasi Kemahasiswaan
- Siskama — SI Penguatan Kapasitas Mahasiswa & Alumni (FT UNG, Laravel), Digital Transformation Technology
- PRABA — Prototipe SI Manajemen Prestasi & Beasiswa Undiksha, JST
- SIKEMAS — SI Kemahasiswaan Politeknik Harapan Bersama, Smart Comp
- Smart Adma — SI Administrasi Kemahasiswaan & Alumni (Extreme Programming)

### Regulasi (WAJIB disitir untuk justifikasi)
- **SIMKATMAWA** (Sistem Informasi Manajemen Tata Kelola & Kinerja Kemahasiswaan) — Diktiristek/Kemendiktisaintek
- **SKPI** (Surat Keterangan Pendamping Ijazah) — standar nasional, untuk topik prestasi

### Machine Learning (klasifikasi/prediksi student)
- Realinho et al. (2021) — Predict Students' Dropout and Academic Success
- Paper XGBoost/Random Forest untuk prediksi kelulusan/dropout mahasiswa Indonesia (JRAMI Unindra, BAREKENG Unpatti, Jurnal STRATEGI Maranatha)

### K-Means Clustering untuk Segmentasi Mahasiswa (PRIORITAS BARU)
- MacQueen, J. (1967) — sumber asli algoritma K-Means
- Paper segmentasi mahasiswa pakai K-Means di jurnal Indonesia (cari di JATISI, SINTA, JURIKOM, SISFOTENIKA)
- Davies-Bouldin (1979) & Silhouette (Rousseeuw, 1987) — metrik evaluasi cluster
- Elbow Method untuk menentukan k optimal

### Sistem Pendukung Keputusan (SPK)
- Turban, Sharda, Delen — *Decision Support and Business Intelligence Systems* (textbook standar)
- Catatan: SPK TIDAK wajib pakai AHP/TOPSIS — bisa pakai ML-based DSS (K-Means, Random Forest, dll.) — landasan teori ini perlu ditegaskan di Bab II

### Content-Based Filtering Akademik
- Rekomendasi topik skripsi / dosen pembimbing / mata kuliah pakai CBF (UGM, UB repositories)

---

## 10. RISIKO & TRADE-OFF YANG SUDAH DIBAHAS

| Metode/Strategi | Risiko |
|---|---|
| XGBoost dengan data kecil | Overfitting; klaim "lebih akurat dari RF" hanya berlaku di data besar |
| Dataset publik luar negeri (Portugal/Cyprus) | Population mismatch, feature mismatch, penguji tanya validitas untuk FT UNSUR |
| Data sintetik | "Garbage in garbage out", metric tidak bermakna, risiko ditolak |
| Random Forest | Aman, stabil di data kecil — pilihan default yang baik |
| Content-Based Filtering | Paling aman untuk data minim (tidak butuh label) |

**Prinsip yang disepakati:** Untuk TA dengan pembimbing ketat + data minim → **kehati-hatian > novelty**. Pilih metode yang JALAN dengan data yang ada. Penguji lebih menghargai sistem yang benar-benar bekerja daripada akurasi tinggi di data simulasi.

---

## 11. KLARIFIKASI KONSEPTUAL PENTING

### XGBoost ≠ GraphRAG (beda total)
- **XGBoost:** ML supervised, data tabular, output label/angka, gratis runtime
- **GraphRAG:** RAG + knowledge graph + LLM, input dokumen + natural language, output teks, butuh LLM (berbayar/GPU), masuk kategori chatbot (dibatasi pembimbing)

### "Beasiswa apa yang cocok untuk saya + jelaskan persyaratannya"
- Bagian "beasiswa cocok" → bisa pakai XGBoost (ranking by probabilitas) ATAU Content-Based Filtering
- Bagian "jelaskan persyaratan" → cukup tampilkan kolom database, TIDAK butuh AI/chatbot
- Tidak perlu GraphRAG/chatbot untuk use case ini

---

## 12. LANGKAH SELANJUTNYA (TODO)

### Tahap Konsultasi (segera)

1. [ ] **Konsultasi Pembimbing 1:** presentasikan judul final K-Means + jelaskan kenapa pivot dari PMB+RF
2. [ ] **Konsultasi Pembimbing 2 (WD III):** presentasikan judul final K-Means + jelaskan K-Means ada di opsi #6 dokumennya + RF jadi saran Bab V
3. [ ] **Sampaikan ke WD III:** kebutuhan data minimum (target 200-300 mahasiswa, minimum 100-150), format IPK per semester, timeline penyerahan
4. [ ] **Klarifikasi:** apakah mahasiswa beasiswa/KKN pakai SIMAFTUNSUR sama atau terpisah?

### Tahap Penulisan Proposal

5. [ ] Susun draft **Bab I** (Latar Belakang, Rumusan Masalah, Tujuan, Batasan, Manfaat) — versi K-Means
6. [ ] Susun draft **Bab II** (Landasan Teori): SIMAFTUNSUR, SIMKATMAWA, SPK, K-Means, Silhouette/DBI/Elbow, kemahasiswaan, penelitian terkait
7. [ ] Susun draft **Bab III** (Metodologi): pengembangan sistem + CRISP-DM/KDD untuk K-Means + skenario evaluasi
8. [ ] Cek template proposal kampus FT UNSUR

### Tahap Persiapan Implementasi

9. [ ] Rancang skema tabel database untuk hasil clustering (siapkan kolom label untuk migrasi RF di masa depan)
10. [ ] Rancang arsitektur integrasi K-Means ke Laravel (Python service via API atau export model)
11. [ ] Mulai input data IPK saat WD III menyerahkan (manual entry kalau hard copy)
12. [ ] Eksplorasi tools visualisasi cluster (untuk dashboard SPK)

---

## 13. FILE PENTING DI REPO

- `docs/CLAUDE_PROJECT_MEMORY.md` — file ini (memory lengkap)
- `docs/RINGKASAN_KONSULTASI_PEMBIMBING.md` — dokumen siap-print untuk konsultasi (ada jejak konsultasi #1 + usulan PMB)
- `docs/FEATURE_DOCUMENTATION_AND_REBUILD_PLAN.md` — dokumentasi fitur & rencana rebuild ke Laravel (detail, panjang)
- `docs/DEPLOY.md`, `docs/RBAC.md`, `docs/STRUCTURE.md`, `docs/PATH_RULES.md` — dokumentasi teknis (sebagian masih ringkas)
- `simaftunsur (1).sql` (di `D:\simaftunsur\`) — dump database

---

## 14. INSTRUKSI UNTUK CLAUDE (Custom Instructions Saran)

> Saya sedang menyusun proposal Tugas Akhir S1 berbasis sistem SIMAFTUNSUR (Sistem Informasi Kemahasiswaan FT Universitas Suryakancana). Bantu saya dengan mematuhi SEMUA ketentuan pembimbing di bagian 3 (terutama daftar metode terlarang: WP, SAW, AHP, TOPSIS, Forward/Backward Chaining, Fuzzy Logic, KNN, Naive Bayes, Regresi Linier — JANGAN sarankan metode ini). Topik beasiswa & KKN sudah diambil mahasiswa lain, jangan sarankan. **Arah final saat ini: SPK Kemahasiswaan + K-Means Clustering** (Random Forest sudah dipertimbangkan tapi ditunda jadi penelitian lanjutan karena data label tidak tersedia). Pivot dari PMB+RF karena Pembimbing 2 (WD III) minta fokus decision making kemahasiswaan. WD III berkomitmen sediakan data IPK per semester. Selalu gunakan bahasa Indonesia baku sesuai tata tulis ilmiah. Setiap judul harus punya 3 unsur: Produk IT, Permasalahan, Sistem Cerdas. Ingat data internal saya minim, jadi pertimbangkan kelayakan data saat menyarankan metode. Jujur soal trade-off, jangan overclaim. JANGAN sarankan AHP "digenerate AI" atau opsi sirkular K-Means→Random Forest dalam satu TA.