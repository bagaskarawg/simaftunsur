# CLAUDE.md — Konteks Proyek SIMAFTUNSUR

> **Untuk Claude Code / sesi Claude di IDE:** File ini berisi seluruh konteks Tugas Akhir S1 berbasis sistem SIMAFTUNSUR. Baca seluruhnya sebelum mengusulkan apapun. JANGAN melanggar ketentuan di Bagian 2 (Aturan Mutlak).
>
> **Terakhir diperbarui:** 2026-05-22
> **Status:** Judul sudah disetujui Pembimbing 1, menunggu konfirmasi Pembimbing 2

---

## 1. JUDUL FINAL TUGAS AKHIR

> ### "Rancang Bangun Sistem Informasi Kemahasiswaan untuk Klasterisasi Profil Mahasiswa Menggunakan Algoritma K-Means"

**Status:** ✅ Disetujui Pembimbing 1 per 2026-05-22

### Pemetaan 3 Unsur Wajib

| Unsur | Isi |
|---|---|
| Produk IT | Sistem Informasi Kemahasiswaan |
| Permasalahan | Klasterisasi/profiling mahasiswa belum tersedia secara sistematis bagi pimpinan |
| Sistem Cerdas | Algoritma K-Means (Clustering) |

### Pemilik Konteks Domain

- **Pembimbing 1:** Otoritas akhir untuk metode/algoritma. Ketat soal larangan.
- **Pembimbing 2 (Wakil Dekan III / WD III):** Pengurus beasiswa & kemahasiswaan. Fokus decision making + kemahasiswaan luas. Berkomitmen menyediakan data IPK per semester.

---

## 2. ATURAN MUTLAK (JANGAN DILANGGAR)

### Metode Algoritma yang DILARANG TOTAL oleh Pembimbing 1

❌ **JANGAN PERNAH SARANKAN/IMPLEMENTASIKAN:**

- WP, SAW, AHP, TOPSIS (MCDM klasik)
- Forward Chaining, Backward Chaining (Sistem Pakar)
- Fuzzy Logic (semua varian: Tsukamoto, Mamdani, Sugeno)
- KNN (K-Nearest Neighbors)
- Naive Bayes
- Regresi Linier / Logistic Regression

### Topik DILARANG/SUDAH DIAMBIL

- ❌ Beasiswa (sudah diambil mahasiswa lain)
- ❌ KKN (sudah diambil mahasiswa lain)
- ❌ Stock Gym
- ❌ Sekolah sebagai tempat penelitian
- ❌ PMB sebagai fokus utama (sudah ditolak Pembimbing 2)

### Aturan Implementasi Penting

- ✅ Algoritma yang dipakai: **K-Means Clustering** (sudah lock)
- ✅ Bahasa: Indonesia baku sesuai PUEBI/EYD untuk dokumentasi & laporan TA
- ✅ Setiap dokumen ilmiah harus jujur soal trade-off, jangan overclaim akurasi
- ✅ Random Forest TIDAK dipakai sekarang (karena data label tidak ada) — akan jadi saran Bab V

---

## 3. STACK TEKNIS

### Lokasi Kode

- **Codebase lama (referensi blueprint):** `D:\simaftunsur\SIMAFTUNSUR\`
- **SQL dump:** `D:\simaftunsur\simaftunsur (1).sql`
- **Stack lama:** PHP 8.x native (tanpa framework), MySQL 8.x, AdminLTE 3, Bootstrap, jQuery

### Stack Target (Rebuild dari Nol)

- **Backend:** Laravel 13 (atau versi stable terbaru)
- **Frontend reactive:** Livewire 4 (atau Livewire stable terbaru)
- **Database:** MySQL 8.x (skema baru, bukan migrasi langsung dari schema lama)
- **CSS:** Tailwind CSS (default Laravel modern)
- **Bahasa ML:** Python (untuk K-Means via scikit-learn)
- **Integrasi ML:** Python service via REST API atau export model (.pkl) → load di Laravel

### Status Modul Existing (sebagai referensi blueprint, bukan basis pengembangan)

| Modul | Status Lama | Catatan |
|---|---|---|
| Beasiswa | ~90% jadi | Skip — diambil mahasiswa lain |
| KKN | ~10% placeholder | Skip — diambil mahasiswa lain |
| Mahasiswa | ~80% jadi | **FOKUS UTAMA** untuk K-Means |
| Promosi/PMB | ~85% jadi | Build sebagai CRUD biasa |
| Prestasi | ~10% placeholder | Build sebagai CRUD biasa |
| Tracer | ~5% placeholder | Build sebagai CRUD biasa |
| Pengguna/RBAC | ~60% | Build ulang dengan Spatie Permission |
| Laporan | ~20% | Build dasar |
| System | ~30% | Build minimal |

### Aturan Arsitektur untuk Implementasi

**Wajib modular** agar mudah migrasi ke Random Forest di masa depan:

```python
# Pipeline ML modular
def preprocess(data): ...           # reusable untuk RF
def feature_engineering(df): ...    # reusable untuk RF
def train_kmeans(X): ...            # ganti jadi train_random_forest(X, y) saat migrasi
def evaluate(model, X): ...         # ganti metrik saat migrasi
```

**Database harus siap menampung label** (status akhir mahasiswa) walau belum digunakan sekarang — untuk migrasi RF di masa depan.

---

## 4. KONDISI DATA (KRITIS — Memengaruhi Desain)

### Data Tersedia

| Sumber Data | Status | Catatan |
|---|---|---|
| IPK per semester | 🔜 Akan disediakan WD III | Boleh hard copy → input manual |
| Status mahasiswa | ✅ Sudah ada di `mhs_mahasiswa` | aktif/cuti/non-aktif |
| Profil mahasiswa | ✅ Sudah ada | nama, NIM, prodi, angkatan, dll. |
| `ft_maba` | ✅ ~178 baris | data mahasiswa baru |
| Data kemahasiswaan tambahan | ⚠️ Perlu cek tabel | organisasi, prestasi |

### Target Volume Data

- **Minimum yang masih bisa diterima:** 100-150 mahasiswa
- **Ideal:** 200-300 mahasiswa
- **Periode mahasiswa:** semester 3 ke atas (sudah ada riwayat IPK 2-3 semester)

### Data yang TIDAK Tersedia

- ❌ Data label status akhir (Lulus Tepat Waktu / Terlambat / DO)
- ❌ Data alumni historis dengan outcome
- ❌ Data SIAKAD (kehadiran, nilai per matkul, SKS, transkrip) — karena SIMAFTUNSUR adalah SI Kemahasiswaan, BUKAN SIAKAD

### Implikasi untuk Algoritma

Karena label tidak ada → wajib pakai **unsupervised learning** → K-Means cocok.

---

## 5. SCOPE PROYEK

### Fokus Penelitian (yang Dievaluasi Ilmiah di Bab IV)

- **Modul:** Sistem Informasi Kemahasiswaan dengan fokus klasterisasi profil mahasiswa
- **Algoritma:** K-Means Clustering
- **Evaluasi:** Silhouette Score, Davies-Bouldin Index, Elbow Method

### Cakupan Sistem yang Dibangun (Produksi)

Seluruh modul SIMAFTUNSUR dengan stack baru:

- ✅ Modul Data Mahasiswa (fokus utama, dengan fitur klasterisasi K-Means)
- ✅ Modul Promosi/PMB (CRUD)
- ✅ Modul Prestasi (CRUD)
- ✅ Modul Tracer Study (CRUD)
- ✅ Modul Pengguna & RBAC
- ✅ Modul Laporan
- ✅ Modul System
- ⚠️ Beasiswa & KKN — koordinasikan dengan mahasiswa lain (apakah satu SIMAFTUNSUR atau terpisah?)

### Yang BUKAN Fokus Penelitian

- Modul-modul CRUD selain Data Mahasiswa tidak dievaluasi ilmiah, tidak dilabeli sistem cerdas
- Tidak ada label "sistem cerdas" untuk modul lain
- Tidak ada evaluasi performa untuk modul lain

---

## 6. KETENTUAN PENULISAN DOKUMEN ILMIAH

### Bahasa & Gaya

- Indonesia baku sesuai PUEBI/EYD
- Sesuai tata tulis laporan ilmiah kampus FT UNSUR
- Hindari klise "Rancang Bangun" di JUDUL BARU — tapi di judul ini sudah disetujui karena memang dibangun dari nol
- Spesifik dan jujur soal limitasi

### Struktur Standar Bab

- **Bab I:** Latar Belakang, Rumusan Masalah, Tujuan, Batasan, Manfaat, Metodologi (ringkas)
- **Bab II:** Landasan Teori (SIMAFTUNSUR, SIMKATMAWA, SI Kemahasiswaan, Klasterisasi, K-Means, Silhouette/DBI/Elbow, Penelitian Terkait)
- **Bab III:** Metodologi Detail (Pengembangan Sistem: SDLC/Agile + CRISP-DM/KDD untuk K-Means)
- **Bab IV:** Implementasi & Pengujian (Hasil clustering + evaluasi metrik)
- **Bab V:** Kesimpulan & Saran (sebutkan Random Forest sebagai pengembangan lanjutan)

### Catatan Penting Pembimbing 2

Walaupun frasa "Pendukung Keputusan" tidak ada di judul (sesuai keinginan Pembimbing 1 untuk pendek), **konsep SPK tetap eksplisit di:**

- Tujuan Penelitian (Bab I)
- Manfaat Penelitian (Bab I)
- Rancangan Output Sistem (Bab III)
- Dashboard hasil klasterisasi (Bab IV)

---

## 7. REFERENSI KUNCI

### Regulasi & Standar (Wajib Disitir)

- **SIMKATMAWA** (Sistem Informasi Manajemen Tata Kelola & Kinerja Kemahasiswaan) — Diktiristek/Kemendiktisaintek
- Standar SI Kemahasiswaan PTN/PTS Indonesia

### K-Means & Evaluasi Cluster

- MacQueen, J. (1967) — algoritma K-Means original
- Davies, D.L. & Bouldin, D.W. (1979) — Davies-Bouldin Index
- Rousseeuw, P.J. (1987) — Silhouette Coefficient
- Elbow Method (Thorndike, 1953)

### Sistem Pendukung Keputusan

- Turban, Sharda, Delen — *Decision Support and Business Intelligence Systems*
- Catatan: SPK TIDAK wajib AHP/TOPSIS — bisa pakai ML-based DSS

### Sistem Informasi Kemahasiswaan (Penelitian Terkait)

- Siskama (FT UNG, Laravel) — SI Penguatan Kapasitas Mahasiswa & Alumni
- PRABA (Undiksha) — SI Manajemen Prestasi & Beasiswa
- SIKEMAS (Politeknik Harapan Bersama) — SI Kemahasiswaan
- Smart Adma — SI Administrasi Kemahasiswaan & Alumni

### Klasterisasi Mahasiswa (Penelitian Terkait)

Cari di jurnal SINTA: JATISI, JURIKOM, SISFOTENIKA, JURTEKSI — paper tentang segmentasi/klasterisasi mahasiswa pakai K-Means

---

## 8. TODO LANGSUNG (Sequential)

### Sebelum Mulai Coding

1. ☐ Konfirmasi judul final ke Pembimbing 2 (WD III)
2. ☐ Klarifikasi komitmen data IPK dari WD III (jumlah, format, timeline)
3. ☐ Klarifikasi koordinasi modul Beasiswa/KKN dengan mahasiswa lain
4. ☐ Daftar GForm + administrasi proposal
5. ☐ Setup project Laravel 13 + Livewire 4 fresh
6. ☐ Setup environment Python (venv/conda) untuk scikit-learn

### Penulisan Proposal

7. ☐ Draft Bab I — Latar Belakang, Rumusan Masalah, Tujuan, Batasan, Manfaat
8. ☐ Draft Bab II — Landasan Teori lengkap
9. ☐ Draft Bab III — Metodologi (SDLC + CRISP-DM)
10. ☐ Review proposal sebelum diserahkan

### Implementasi Sistem

11. ☐ Rancang skema database (siapkan kolom label untuk future RF)
12. ☐ Buat skema RBAC (Spatie Permission)
13. ☐ Bangun modul autentikasi
14. ☐ Bangun modul Data Mahasiswa (CRUD + import IPK)
15. ☐ Bangun modul-modul pendukung (CRUD)
16. ☐ Bangun pipeline ML K-Means (Python)
17. ☐ Integrasi K-Means dengan Laravel (API/export model)
18. ☐ Bangun dashboard hasil klasterisasi
19. ☐ Testing & debugging

---

## 9. PRINSIP KERJA UNTUK CLAUDE CODE

Saat Anda (Claude Code) membantu di proyek ini, patuhi prinsip-prinsip ini:

### Yang HARUS Dilakukan

- ✅ Selalu cek ulang aturan di Bagian 2 sebelum menyarankan algoritma/metode
- ✅ Gunakan **Laravel 13 + Livewire 4** dengan praktik terbaik (Eloquent, Form Requests, Policies, dll.)
- ✅ Tulis kode modular agar mudah migrasi ke Random Forest
- ✅ Komentar kode dalam Bahasa Indonesia baku (untuk konsistensi dengan laporan TA)
- ✅ Pertimbangkan kondisi data minim — JANGAN buat solusi yang butuh data besar
- ✅ Jujur soal trade-off jika algoritma/pendekatan punya keterbatasan
- ✅ Pakai PSR-12 untuk PHP, PEP-8 untuk Python
- ✅ Setiap fitur ML harus punya pengujian metrik yang relevan (Silhouette, DBI, Elbow)

### Yang TIDAK Boleh Dilakukan

- ❌ Sarankan AHP, TOPSIS, SAW, WP, Fuzzy Logic, KNN, Naive Bayes, Forward/Backward Chaining, Regresi Linier
- ❌ Sarankan topik beasiswa atau KKN sebagai fokus
- ❌ Klaim akurasi tinggi dari data sintetik/dummy
- ❌ Bangun fitur untuk modul Beasiswa/KKN tanpa konfirmasi koordinasi
- ❌ Pakai kata "Rancang Bangun" untuk JUDUL atau MODUL BARU (kecuali memang dibangun dari nol — judul TA ini diizinkan karena sistem memang dari nol)
- ❌ Sarankan Random Forest sekarang (tidak ada data label) — itu pengembangan lanjutan
- ❌ Buat database schema yang mengasumsikan ada data SIAKAD (kehadiran, nilai matkul)

### Jika Ragu

Tanyakan dulu sebelum mengambil keputusan besar:
- Algoritma alternatif?
- Library/framework alternatif?
- Skema database yang significant berubah?
- Pendekatan implementasi yang berbeda dari yang sudah ditetapkan?

---

## 10. KONTEKS HISTORIS (Untuk Pemahaman Anda)

Jejak iterasi sebelum mencapai judul final, agar Anda paham mengapa kondisi sekarang seperti ini:

1. **Awal:** User pilih PMB + Random Forest (data internal lengkap)
2. **Konflik:** Pembimbing 2 (WD III) tidak setuju fokus PMB
3. **Iterasi:** Pembimbing 2 usulkan AHP/TOPSIS/Fuzzy Logic — semua DILARANG Pembimbing 1
4. **Solusi data:** WD III berkomitmen sediakan IPK per semester
5. **Solusi metode:** Pakai K-Means (unsupervised, tidak butuh label) karena data label tidak ada
6. **Iterasi judul:**
 - "Pengembangan SPK + Hybrid ML" ❌ (dilarang Pembimbing 1)
 - "Pengembangan SPK + K-Means" ❌ (SPK butuh sistem pendahulu)
 - "Pengembangan SI Kemahasiswaan + Modul SPK + K-Means" ❌ (pakai "Pengembangan" padahal belum ada → "Rancang Bangun")
 - "Rancang Bangun SI Kemahasiswaan + Modul SPK + K-Means Clustering" ❌ (terlalu panjang, "aneh")
 - **"Rancang Bangun SI Kemahasiswaan untuk Klasterisasi Profil Mahasiswa Menggunakan Algoritma K-Means"** ✅ DISETUJUI

### Pelajaran Penting dari Iterasi

- Aturan "jangan pakai Rancang Bangun" → tidak mutlak, hanya untuk sistem yang sebenarnya "rebrand"
- SPK butuh sistem pendahulu (operasional) → arsitektur klasik Turban
- "Klasterisasi" dalam judul = istilah Indonesia baku, "K-Means Clustering" lengkap = redundan
- Output K-Means yang dipakai untuk pengambilan keputusan = sudah cukup mewakili konsep SPK tanpa harus disebut di judul

---

## 11. KONTAK & ENVIRONMENT

### Identitas Proyek

- **Nama Sistem:** SIMAFTUNSUR (Sistem Informasi Kemahasiswaan Fakultas Teknik Universitas Suryakancana)
- **Studi Kasus:** Fakultas Teknik, Universitas Suryakancana (UNSUR), Cianjur
- **Periode TA:** Mei 2026 - (proyeksi selesai akhir 2026)

### Aturan Operasional Claude Code

- Bahasa interaksi default: **Bahasa Indonesia**
- Bahasa kode (komentar, dokumentasi inline): **Bahasa Indonesia**
- Bahasa identifier (nama variabel, function, class): **Bahasa Indonesia** (snake_case untuk variable/method, PascalCase untuk class) — diputuskan 2026-05-23 demi konsistensi dengan laporan TA
- Bahasa nama tabel/kolom database: **Bahasa Indonesia snake_case** (mis. `pengguna`, `kata_sandi`, `nip`, `peran`)

### Pengecualian (tetap memakai Bahasa Inggris karena dipaksa Laravel/paket pihak ketiga)

- Konfigurasi & ENV keys: `APP_NAME`, `DB_CONNECTION`, dll. (Laravel core)
- Method bawaan framework yang di-override: `boot()`, `register()`, `handle()`, `up()`, `down()`, dll.
- Trait & interface Laravel: `HasFactory`, `Notifiable`, `Authenticatable`, dll. — pakai apa adanya
- Nama paket Composer: `spatie/laravel-permission` (tabel paket: `roles`, `permissions` — kalau memungkinkan rename ke `peran`, `izin` via config publish)
- Frontend asset path & route name internal Laravel (mis. `route('login')`) — boleh Indonesia untuk route custom (`route('beranda')`)

---

**END OF CLAUDE.md**

> Jika Anda (Claude Code) menemukan situasi yang tidak tercakup di dokumen ini, **tanyakan terlebih dahulu** kepada user sebelum mengambil keputusan besar. Jangan asumsikan sendiri.
