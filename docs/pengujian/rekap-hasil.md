# Rekap Hasil Pengujian (siap salin ke BAB IV, Tabel 4.4–4.8)

Ringkasan hasil pengujian berjenjang SIMAFTUNSUR. Seluruh uji dijalankan otomatis
(`php artisan test` untuk aplikasi Laravel, `pytest` untuk service Python).
Artefak mentah (JUnit XML & coverage) ada di `docs/pengujian/artefak/`.

**Total: 159 uji Laravel + 15 uji pytest = 174 uji, seluruhnya LOLOS.**
Cakupan kode service Python (pytest --cov): **90%**.

> Pemisahan penting (kejujuran): pengujian FUNGSIONAL memakai data SIMULASI;
> analisis klasterisasi ilmiah tetap memakai data IPK RIIL (F1–F4). Data emas
> BAB III dihitung ulang dari kode — lihat DF-03 di `defects.md`.

---

## Tabel 4.4 — T1 Unit (whitebox)

| Kode | Skenario | Hasil | Keterangan |
|------|----------|-------|------------|
| U-01 | Fitur akademik (IPK rata/terakhir/tren/konsistensi) M1–M9 sesuai data emas | Lolos | `tests/Unit/FiturAkademikTest.php`; konsistensi = std-dev populasi |
| U-02 | StandardScaler menghasilkan μ/σ (ddof=0) & matriks standardisasi acuan | Lolos | `ml/tests/test_scaler.py`; assert eksplisit ddof=0 |
| U-03 | Jarak Euclidean konsisten & tiap titik terdekat ke centroidnya sendiri | Lolos | `ml/tests/test_jarak.py` |
| U-04 | Poin rubrik SKKM (Tabel 5–7) + plafon (maks 3/grup/tahun) | Lolos | `tests/Unit/RubrikSkkmTest.php`; lihat DF-01 |
| U-05 | Guard data minimum: <3 catatan dikecualikan; <3 layak ditolak; <100 tak diblokir | Lolos | `tests/Unit/GuardDataMinimumTest.php` |

## Tabel 4.5 — Pengujian Model Klasterisasi (pytest, data emas)

| Kode | Skenario | Hasil | Keterangan |
|------|----------|-------|------------|
| MK-01 | Silhouette & DBI terhitung wajar (k=2..6); k=3 kohesif | Lolos | `test_metrik.py`; silhouette memuncak di k=2 → dasar pakai Elbow |
| MK-02 | Uji Kruskal-Wallis: fitur IPK berbeda nyata antar klaster (p<0,05) | Lolos | `test_kruskal.py` |
| MK-03 | Bootstrap-Jaccard: klaster stabil (>0,75) & deterministik (seed) | Lolos | `test_jaccard.py` |
| MK-04 | Pipeline ujung-ke-ujung: partisi emas benar, Elbow pilih k=3, deterministik | Lolos | `test_pipeline.py` |

## Tabel 4.6 — T3 Integrasi (aplikasi ↔ service Python)

| Kode | Skenario | Hasil | Keterangan |
|------|----------|-------|------------|
| I-01 | `/sehat` hidup→true, mati→false; service mati ditangani (pesan, bukan 500) | Lolos | `tests/Integration/KlasterisasiIntegrasiTest.php` |
| I-02 | Data emas dikirim & partisi hasil dipetakan ke DB (label-invariant) | Lolos | payload fitur benar + partisi tersimpan = emas |
| I-03 | Validasi antar-tahap: scaler mean≈0/std≈1, tabel evaluasi-k valid | Lolos | `ml/tests/test_integrasi_tahap.py` |
| I-04 | Snapshot & metrik tersimpan; riwayat stabil meski IPK berubah | Lolos | fitur_nilai dibekukan |

## Tabel 4.7 — T2 Modul (feature test per modul)

Kode M-xx dipetakan ke berkas uji modul yang sudah ada (CRUD, validasi, alur).

| Kode | Modul / Skenario | Hasil | Berkas |
|------|------------------|-------|--------|
| M-01 | Data Mahasiswa (CRUD, validasi, filter) | Lolos | `tests/Feature/Mahasiswa/*` |
| M-02 | Impor IPK & Mahasiswa (berkas valid & rusak) | Lolos | `tests/Feature/Imports/*` |
| M-03 | Klasterisasi (kesiapan, jalankan, halaman, detail) | Lolos | `tests/Feature/Klasterisasi/*` |
| M-04 | Kategori Klaster (katalog label + sisip level) | Lolos | `tests/Feature/Klasterisasi/KategoriKlasterTest.php` |
| M-05 | Pengguna & RBAC (peran, izin) | Lolos | `tests/Feature/Pengguna/*`, `tests/Feature/Rbac/*` |
| M-06 | Penyaringan Kandidat (program, syarat, evaluator) | Lolos | `tests/Feature/Penyaringan/*` |
| M-07 | Laporan, Prestasi, Kegiatan/SKKM, Beranda, dll | Lolos | `tests/Feature/{Laporan,Prestasi,Skkm,Beranda,...}/*` |

## Tabel 4.8 — T4 Sistem (blackbox)

**Fungsional (KF)**

| Kode | Skenario | Hasil | Keterangan |
|------|----------|-------|------------|
| KF-01 | Login valid berhasil; login salah ditolak | Lolos | `tests/Feature/Sistem/FungsionalTest.php` |
| KF-02 | Akses menu sesuai peran | Lolos | admin/wd3/staf_prodi |
| KF-03 | Tambah data mahasiswa (CRUD) | Lolos | |
| KF-04 | Impor massal (valid & rusak) | Lolos | dipetakan ke `tests/Feature/Imports/*` |
| KF-05 | Jalankan klasterisasi data layak → hasil tersimpan | Lolos | service ditiru |
| KF-06 | Tampil hasil & riwayat klaster | Lolos | dipetakan ke `KlasterisasiHalamanTest` |
| KF-07 | Dashboard WD III tampil | Lolos | |
| KF-08 | Unduh laporan (CSV) | Lolos | |

**Data (D)**

| Kode | Skenario | Hasil |
|------|----------|-------|
| D-01 | IPK di luar 0–4 ditolak | Lolos |
| D-02 | NPM ganda ditolak | Lolos |
| D-03 | Hapus mahasiswa membersihkan riwayat IPK (integritas relasi) | Lolos |
| D-04 | Eksekusi ditolak bila data layak < 3 | Lolos |

**Keamanan (S)**

| Kode | Skenario | Hasil |
|------|----------|-------|
| S-01 | Akses tanpa login → redirect ke login | Lolos |
| S-02 | Akses lintas peran → 403 | Lolos |
| S-03 | XSS di-escape; SQLi tak merusak kueri | Lolos |
| S-04 | Kata sandi disimpan sebagai hash | Lolos |

---

## Cara menjalankan & menghasilkan artefak

```bash
# Aplikasi (Laravel/Pest) + JUnit XML
php artisan test --log-junit docs/pengujian/artefak/laravel-junit.xml

# Service (pytest) + JUnit XML + coverage
cd ml
python -m pytest --junitxml=../docs/pengujian/artefak/pytest-junit.xml --cov=pipeline
```
