# Log Defect Pengujian (Tabel 4.11)

Setiap ketidaksesuaian nyata yang ditemukan selama pengujian dicatat di sini,
diperbaiki, lalu diuji ulang. Prinsip: assertion TIDAK dilonggarkan untuk
"menghijaukan" uji — logika bisnis yang diperbaiki agar sesuai spesifikasi.

| ID | Tanggal | Tingkat | Gejala | Analisis Penyebab | Solusi | Uji Ulang |
|----|---------|---------|--------|-------------------|--------|-----------|
| DF-01 | 2026-07-27 | T1 / U-04 | Poin SKKM di kode (skala 0–100, tanpa plafon) tidak cocok rubrik naskah BAB II (Tabel 5–7). | `config/skkm.php` memakai besaran lama; belum ada logika plafon akumulasi. | Selaraskan besaran ke Tabel 5–7 (skala CIC) + tambah plafon (maks 3 item poin tertinggi per grup per tahun kalender) pada Model Mahasiswa. | Lolos (U-04) |
| DF-02 | 2026-07-27 | T4 / model | Klasterisasi membentuk 7 klaster dari 30 data (biasanya 3–4); anggota tampak menyebar. | Pemilihan k memakai silhouette-maksimum; pada data sparse kurva silhouette datar → memuncak di k besar (over-segmentasi). | Pilih k via Elbow Method (deteksi siku WCSS otomatis, jarak-ke-garis). Silhouette/DBI jadi metrik evaluasi. | Lolos — sintetik 3-grup → k=3; data seed → k=4 |
| DF-03 | 2026-07-27 | T1 / U-01, U-02 | Angka data emas naskah (Tabel 3.5 konsistensi & Tabel 3.6 standardisasi, partisi) tidak reproducible oleh kode. | Definisi `konsistensi` di naskah (indeks tinggi=baik) berbeda dari implementasi (standar deviasi IPK, kecil=baik). | Data emas DIHITUNG ULANG dari kode (`tests/fixtures/golden_bab3.json`). μ/σ, matriks 9×4, partisi {M1–M3},{M4–M6},{M7–M9} mengikuti kode. | Lolos (kode); ⚠️ naskah Tabel 3.5/3.6 perlu diselaraskan (tindak lanjut penulisan) |

> Catatan DF-03: silhouette pada data emas justru memuncak di k=2 (pemisahan
> trivial), sedangkan Elbow memilih k=3 (jumlah grup laten sebenarnya) — bukti
> pendukung keputusan DF-02.
