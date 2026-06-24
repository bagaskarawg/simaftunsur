# CLAUDE.md — Konteks Proyek SIMAFTUNSUR (Handoff untuk Claude Code)

> **Untuk Claude Code / sesi Claude di IDE:** File ini berisi seluruh konteks Tugas Akhir S1 berbasis sistem SIMAFTUNSUR. **Baca seluruhnya sebelum mengusulkan apa pun.** JANGAN melanggar ketentuan di Bagian 2 (Aturan Mutlak).
>
> **Terakhir diperbarui:** 2026-06-24 (diselaraskan dengan naskah proposal ber-ACC)
> **Status proposal:** ✅ ACC — Lembar Usulan ditandatangani Koordinator TA (Agus Suheri, S.T., M.Kom) tertanggal 2 Juni 2026. BAB I–III sudah didraf.
>
> **Sumber kebenaran (urutan otoritas):**
> 1. Naskah proposal `RANCANG_BANGUN...K-MEANS` (.docx/.pdf) — paling otoritatif untuk isi ilmiah.
> 2. File ini (CLAUDE.md) + CLAUDE_PROJECT_MEMORY.md.
> 3. Instruksi inline dalam chat — **jika bertentangan dengan 1 & 2, konfirmasi dulu, jangan ikuti buta.**
 
---

## 1. JUDUL FINAL TUGAS AKHIR (TERKUNCI)

> ### "Rancang Bangun Sistem Informasi Kemahasiswaan untuk Klasterisasi Profil Mahasiswa Menggunakan Algoritma K-Means"

**Status:** ✅ Final & ber-ACC. Jangan diubah tanpa instruksi eksplisit.

### Pemetaan 3 Unsur Wajib

| Unsur | Isi |
|---|---|
| Produk IT | Sistem Informasi Kemahasiswaan (SIMAFTUNSUR) berbasis web |
| Permasalahan | Data kemahasiswaan tersebar & *profiling* mahasiswa masih manual/berbasis asumsi, sehingga pimpinan sulit merumuskan strategi pembinaan tepat sasaran |
| Sistem Cerdas | Algoritma **K-Means Clustering** (*unsupervised*) |

### Identitas Pengusul & Pembimbing

- **Mahasiswa:** Bagaskara Wisnu Gunawan — NPM 5520119124 — Teknik Informatika FT UNSUR, Cianjur.
- **Pembimbing 1:** Tarmin Abdulghani, S.T., M.T. — otoritas akhir untuk metode/algoritma; ketat soal larangan metode. (Di Lembar Usulan juga tercatat sebagai Dosen Wali.)
- **Pembimbing 2 / Wakil Dekan III (WD III):** peminta fokus klasterisasi kemahasiswaan; **penyedia data IPK per semester**; pengguna akhir dashboard.
- **Koordinator TA:** Agus Suheri, S.T., M.Kom (NIDN 0003127201) — penerbit ACC.
---

## 2. ATURAN MUTLAK (JANGAN DILANGGAR)

### Metode Algoritma yang DILARANG TOTAL oleh Pembimbing 1

❌ **JANGAN PERNAH SARANKAN/IMPLEMENTASIKAN:**

- WP, SAW, AHP, TOPSIS (MCDM klasik)
- Fuzzy Logic (semua varian: Tsukamoto, Mamdani, Sugeno) & Fuzzy AHP
- Forward Chaining, Backward Chaining (Sistem Pakar)
- Profile Matching
- KNN (K-Nearest Neighbors)
- Naive Bayes
- Regresi Linier / Logistic Regression
### Aturan Metode Inti

- ✅ Algoritma inti **terkunci: K-Means Clustering** (*unsupervised*, tidak butuh label).
- ⚠️ **Random Forest BUKAN metode TA ini.** Ia hanya boleh muncul sebagai **saran pengembangan lanjutan di BAB V**. Jangan jadikan fokus, jangan implementasikan sebagai metode penelitian. (PMB + Random Forest adalah arah lama yang **sudah dibatalkan** — lihat Bagian 10.)
- ✅ Evaluasi klaster **wajib internal**: Silhouette Coefficient, Davies-Bouldin Index, Elbow Method (WCSS). BUKAN Accuracy/Precision/Recall (karena *unsupervised*).
### Topik / Fokus DILARANG atau SUDAH DIAMBIL

- ❌ **Beasiswa** (diambil mahasiswa lain) — di SIMAFTUNSUR cukup CRUD pendukung.
- ❌ **KKN** (diambil mahasiswa lain) — cukup CRUD pendukung.
- ❌ **PMB/Promosi sebagai fokus penelitian** (ditolak Pembimbing 2) — hanya CRUD pendukung.
- ❌ Stock Gym; ❌ Sekolah sebagai tempat penelitian.
### Aturan Penulisan & Integritas

- ✅ Bahasa Indonesia baku (PUEBI/EYD), gaya tata tulis ilmiah FT UNSUR.
- ✅ **Jujur soal trade-off**; jangan *overclaim* akurasi/kualitas klaster.
- ✅ Semua sitasi **wajib terbit ≥ 2015** (maksimal 10 tahun per 2026).
- ❌ Jangan klaim hasil dari data sintetik/dummy sebagai bukti kualitas.
---

## 3. METODOLOGI (Sesuai Naskah — JANGAN diganti ke "SDLC/Agile")

- **Metode pengembangan perangkat lunak: Waterfall (Pressman & Maxim, 2020)** — utama & sekuensial, 5 tahap:
    1. Analisis Kebutuhan (*Requirements Analysis*) → SRS.
    2. Perancangan Sistem (*System Design*) → ERD, UML (use case, activity, class), UI, rancangan *pipeline* K-Means.
    3. Implementasi (*Implementation*) → Laravel + MySQL (SI) & Python + scikit-learn (klasterisasi), integrasi via *service*/REST API.
    4. Pengujian (*Testing*) → **Black Box Testing** (fungsional) + metrik klaster (Silhouette/DBI/Elbow).
    5. Pemeliharaan (*Maintenance*) → ringkas (bug fix + dokumentasi).
- **CRISP-DM: pendekatan KOMPLEMENTER, hanya untuk komponen klasterisasi**, terintegrasi di dalam tahapan Waterfall. Bukan metodologi utama. Enam fase: Business/Data Understanding, Data Preparation, Modeling, Evaluation, Deployment.
---

## 4. STACK TEKNIS

### Stack Target (Rebuild dari Nol)

- **Backend:** Laravel 13 (atau stable terbaru). *(Naskah menyebut "Laravel" generik; spesifik versi 13 sesuai keputusan teknis.)*
- **Frontend reactive:** Livewire 4.
- **CSS:** Tailwind CSS (default Laravel modern).
- **Database:** MySQL 8.x (skema baru, bukan migrasi langsung dari skema lama).
- **ML:** Python + **scikit-learn** untuk K-Means; pra-pemrosesan NumPy/Pandas.
- **Integrasi ML:** *Python service via REST API* (atau ekspor model `.pkl` → load di Laravel).
- **Auth/RBAC:** bangun ulang (mis. Spatie Permission).
### Codebase Lama (referensi blueprint, BUKAN basis pengembangan)

- Lokasi: `D:\simaftunsur\SIMAFTUNSUR\` (PHP 8 native tanpa framework, AdminLTE 3, jQuery).
- SQL dump: `D:\simaftunsur\simaftunsur (1).sql`.
- 10 modul / 79 file PHP; banyak placeholder. Lihat tabel status modul di CLAUDE_PROJECT_MEMORY.md §2.
### Pipeline ML — WAJIB Modular

```python
# Modular agar komponen reusable & rapi (BUKAN untuk pamer migrasi RF dini)
def preprocess(data): ...            # missing value, outlier, encoding
def feature_engineering(df): ...     # skala atribut (StandardScaler / MinMax)
def train_kmeans(X, k): ...          # KMeans++ init, scikit-learn
def evaluate(model, X): ...          # Silhouette, Davies-Bouldin, Elbow (WCSS)
def interpret_clusters(model, df): ...# karakteristik centroid + PCA untuk visualisasi
```

> Skema DB boleh menyiapkan kolom status akhir mahasiswa untuk keperluan masa depan, **tetapi jangan mengimplementasikan Random Forest sekarang**.
 
---

## 5. KONDISI DATA (KRITIS — Memengaruhi Desain)

| Sumber Data | Status | Catatan |
|---|---|---|
| IPK per semester | 🔜 Disediakan WD III | Boleh hard copy → input manual |
| Status mahasiswa | ✅ Ada | aktif/cuti/non-aktif |
| Profil mahasiswa | ✅ Ada | nama, NPM, prodi, angkatan, semester |
| Data label status akhir (Lulus/Terlambat/DO) | ❌ TIDAK ADA | → wajib *unsupervised* |
| Data SIAKAD (kehadiran, nilai matkul, SKS) | ❌ TIDAK ADA | SIMAFTUNSUR = SI Kemahasiswaan, **bukan SIAKAD** |

- **Atribut klasterisasi:** IPK per semester, status studi, semester berjalan, program studi, data kemahasiswaan terkait.
- **Volume:** minimum **100 mahasiswa aktif** yang telah menempuh **≥ 3 semester** (per Batasan Masalah); ideal 200–300.
- **Implikasi:** label tidak ada → **K-Means (unsupervised) adalah pilihan yang benar & realistis.**
---

## 6. SCOPE: FOKUS PENELITIAN vs CAKUPAN SISTEM

- **Fokus penelitian (masuk judul + dievaluasi ilmiah di BAB IV):** Modul Data Mahasiswa + **klasterisasi K-Means** + dashboard hasil.
- **Cakupan sistem (dibangun, tapi BUKAN fokus, TANPA sistem cerdas):** modul Beasiswa, KKN, **Promosi/PMB**, Prestasi, Tracer Study, Pengguna/RBAC, Laporan, System — semuanya **CRUD biasa**.
- Jangan beri label "sistem cerdas" atau evaluasi performa pada modul pendukung.
---

## 7. STATUS NASKAH & ITEM AKTIF

### Sudah selesai
- ✅ Proposal ber-ACC; **BAB I–III didraf** (DOCX sudah dihasilkan).
### Item yang masih harus dikerjakan
1. ☐ **Rekonsiliasi diagram BAB III dengan codebase nyata.** ERD, class diagram, use case saat ini dibangun di atas skema asumsi → harus diselaraskan. **Minta Claude Code keluarkan SUMBER TEKS diagram (Mermaid/PlantUML), bukan gambar render**, agar mudah direvisi pembimbing. ERD = `erDiagram` Mermaid (hanya tabel bisnis, **buang tabel sistem Laravel**: migrations, jobs, cache, dll.). Class diagram dari Models/Controllers. Aktor & use case dari `routes/web.php` + middleware.
2. ☐ **Activity diagram alur klasterisasi K-Means** (langkah berikutnya melengkapi set diagram).
3. ☐ **Pembersihan Mendeley (MANUAL di Reference Manager):** 6 pasang entri ganda penyebab sufiks "a/b" — Han 2022, Ishak 2024, Pratama 2022, Pressman 2020, Primartha 2021, Sholeh 2022 — plus 1 entri korup **Suryaningrum 2023**.
4. ☐ **Penelitian Terkait BAB II:** 4 sistem sudah terverifikasi (Siskama, PRABA, SIKEMAS, Smart Adma). Sistem ke-5 = **TA Siti Mariam (NPM 5520117021)** harus diambil **fisik** di perpustakaan FT UNSUR (tidak ada online).
5. ☐ **Draf BAB IV–V** — target akhir Juli; **Sidang awal Agustus**; **Seminar awal Juli** (butuh BAB I–III final).
---

## 8. REFERENSI KUNCI (yang sudah dipakai di naskah)

> Semua ≥ 2015. *(Sufiks a/b pada entri di bawah adalah artefak duplikasi Mendeley yang harus dibersihkan — lihat §7 no. 3.)*

- **Anggraeni & Irviani (2017)** — *Pengantar Sistem Informasi*, Andi Yogyakarta. Key: `anggraeni2017pengantar`.
- **Han, Kamber & Pei (2022)** — *Data Mining: Concepts and Techniques* (4th ed.), Morgan Kaufmann.
- **Pressman & Maxim (2020)** — *Software Engineering: A Practitioner's Approach* (9th ed.), McGraw-Hill (sumber Waterfall).
- **Primartha (2021)** — *Algoritma Machine Learning*, Informatika.
- **Sholeh, Ghufron & Fatkhiyah (2022)** — perbandingan Davies-Bouldin/Elbow/Silhouette, *STRING*.
- **Ishak, Dali & Pakaya (2024)** — Clustering Prestasi Akademik Lulusan dengan K-Means, *Jambura JEEE*.
- **Pratama, Puspitasari & Tolle (2022)** — Analisis Prestasi Akademik dengan Clustering, *JPTIIK*.
- **Hardianti & Agushinta R. (2020)** — Pola Masa Studi dengan K-Means, *JTIIK*.
- **Hasan, Wahyudi & Hendra (2024)** — Silhouette & DBI pada K-Means/DBSCAN, *JITET*.
- **Suryaningrum dkk. (2023)** — Optimasi jumlah klaster K-Means dengan Silhouette/DBI, *BAREKENG*. *(entri perlu perbaikan)*
- **McKinney (2022)** — *Python for Data Analysis* (3rd ed.), O'Reilly.
- **Laudon & Laudon (2020)** — *Management Information Systems* (16th ed.), Pearson.
- **Direktorat Jenderal Pendidikan Tinggi (2023)** — Pedoman **SIMKATMAWA**, Kemendikbudristek.
---

## 9. PRINSIP KERJA UNTUK CLAUDE CODE

### HARUS
- ✅ Cek ulang Bagian 2 sebelum menyarankan algoritma/metode apa pun.
- ✅ Pakai **Laravel 13 + Livewire 4** dengan praktik terbaik (Eloquent, Form Request, Policy, migration).
- ✅ Komentar kode **Bahasa Indonesia baku**; identifier (variabel/method **camelCase Indonesia**, kelas/Model **PascalCase Indonesia** mis. `Pengguna`, `NilaiIpkSemester`); nama tabel/kolom **snake_case Indonesia** (mis. `pengguna`, `kata_sandi`, `nilai_ipk_semester`, `semester_aktif`). *(Keputusan 2026-05-23, dipakai konsisten di seluruh codebase. Pengecualian tetap Inggris: API framework/paket yang di-hard-code — mis. kolom `user_id` di tabel `sessions`, method `boot()/up()/down()`, trait `HasFactory`.)*
- ✅ PSR-12 (PHP), PEP-8 (Python).
- ✅ Pertimbangkan **data minim** — jangan rancang solusi yang butuh data besar.
- ✅ Untuk diagram: keluarkan **sumber teks Mermaid/PlantUML** (bukan PNG), hanya tabel/relasi bisnis.
- ✅ Jujur soal keterbatasan.
### TIDAK BOLEH
- ❌ Sarankan WP/SAW/AHP/TOPSIS/Fuzzy/Fuzzy AHP/Profile Matching/Forward-Backward Chaining/KNN/Naive Bayes/Regresi Linier/Logistic Regression.
- ❌ Jadikan **PMB/Promosi atau Random Forest** sebagai fokus/metode penelitian (RF hanya BAB V).
- ❌ Beri label sistem cerdas / evaluasi performa pada modul pendukung.
- ❌ Asumsikan ada data SIAKAD (kehadiran, nilai matkul) di skema DB.
- ❌ Klaim akurasi dari data dummy.
### Jika ragu → **tanya dulu** (perubahan algoritma, framework, skema DB signifikan, atau pendekatan yang menyimpang dari yang sudah ditetapkan). Jangan asumsikan sendiri.
 
---

## 10. KONTEKS HISTORIS (Agar Paham Kondisi Sekarang)

1. **Awal:** user pilih **PMB + Random Forest** (data internal terasa lengkap).
2. **Konflik:** Pembimbing 2 (WD III) menolak fokus PMB; minta fokus *decision making* kemahasiswaan luas.
3. WD III sempat usul AHP/TOPSIS/Fuzzy → **semua dilarang Pembimbing 1**.
4. **Solusi data:** WD III berkomitmen sediakan IPK per semester; **data label status akhir tidak ada**.
5. **Solusi metode:** pilih **K-Means** (unsupervised) — memuaskan WD III, aman dari larangan, realistis dengan data.
6. **Random Forest digeser** jadi saran BAB V (butuh data berlabel yang belum tersedia).
7. Judul difinalkan & **di-ACC** sebagai "Rancang Bangun ... K-Means".
> ⚠️ **Karena itu, klaim "arah saat ini PMB + Random Forest" = arah lama yang sudah dibatalkan.** Jika muncul lagi di instruksi, konfirmasikan ke user sebelum mengikuti.
 
---

## 11. ENVIRONMENT & OPERASIONAL

- **Sistem:** SIMAFTUNSUR — SI Kemahasiswaan FT Universitas Suryakancana (UNSUR), Cianjur.
- **Periode TA:** Mei–Juli 2026 (Seminar awal Juli, Sidang awal Agustus).
- **Workflow file proyek di Claude.ai:** konteks otoritatif di `/mnt/project/`; output ke `/mnt/user-data/outputs/`.
- **Workflow DOCX:** `unpack.py` → edit `word/document.xml` → `pack.py`. Untuk baca teks: `cat`/`grep`/`sed` andal; `pandoc`/`extract-text` tidak andal di environment ini.
- **Navigasi file teks besar:** `grep -nE "^(###|\*\*[0-9]|## )"` untuk heading, lalu `sed -n` untuk baca bertarget.
---

**END OF CLAUDE.md** — Jika menemukan situasi di luar dokumen ini, **tanyakan dulu** sebelum keputusan besar.
