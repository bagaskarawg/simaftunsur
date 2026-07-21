# Metode Pelabelan Klaster — Skor Komposit Multi-Fitur

> Menjawab pertanyaan: **"Bagaimana cara menentukan label klaster (mis. 'Berprestasi')?
> Apakah hanya dari IPK tertinggi?"** — Tidak. Sejak revisi ini, label ditentukan dari
> **skor komposit gabungan fitur akademik + non-akademik**, bukan peringkat IPK semata.
> Implementasi: `ml/pipeline/pelabelan.py` + `ml/pipeline/interpret.py`.
> Terakhir diperbarui: 2026-07-03.

## 1. Latar: kenapa IPK saja tidak cukup

Pimpinan (WD III) tidak ingin IPK menjadi satu-satunya "sasaran". Contoh nyata:
mahasiswa ber-IPK 4,0 tetapi tidak pernah ikut kegiatan, tidak punya prestasi, dan
tidak ikut lomba — belum tentu layak disebut "berprestasi" secara menyeluruh, dan
belum tentu direkomendasikan untuk beasiswa dibanding mahasiswa ber-IPK sedang yang
aktif berorganisasi dan banyak prestasi. Karena itu pelabelan harus **multi-fitur**.

## 2. Dua tahap yang terpisah

1. **K-Means membentuk klaster** (tanpa label). Tiap mahasiswa = titik di ruang
   7 fitur terstandardisasi; keanggotaan ditentukan kedekatan (jarak Euclidean) ke
   centroid, bukan "nilai tertinggi". K-Means tidak tahu istilah "Berprestasi".
2. **Pelabelan pasca-klaster** (bagian ini). Setelah klaster terbentuk, tiap klaster
   diberi skor komposit lalu diperingkat dan dinamai. Label = **alat bantu interpretasi
   berbasis aturan yang transparan**, bukan bagian dari algoritma K-Means.

## 3. Dasar perhitungan: centroid terskala (z-score)

Pelabelan memakai **centroid pada ruang terskala** (hasil `StandardScaler`). Tiap
nilai centroid adalah rata-rata **z-score** fitur pada klaster tersebut:

$$z = \frac{x - \mu}{\sigma}$$

Keunggulannya: fitur berbeda satuan (IPK 0–4 vs poin SKKM yang bisa ratusan) menjadi
**setara** dan boleh dijumlahkan berbobot. Interpretasi nilai: z ≈ 0 = rata-rata
populasi; z positif = di atas rata-rata; z negatif = di bawah rata-rata.

## 4. Rumus skor komposit

**Tujuh fitur, dua dimensi:**

| Dimensi | Fitur | Bobot fitur | Arah |
|---|---|---|---|
| **Akademik** (bobot 0,5) | IPK rata-rata | 0,40 | + |
| | IPK terakhir | 0,30 | + |
| | Tren | 0,20 | + |
| | Konsistensi (std deviasi IPK) | 0,10 | **−** (makin kecil makin stabil) |
| **Non-akademik** (bobot 0,5) | Skor prestasi (F5) | 0,50 | + |
| | Skor kegiatan (F6) | 0,30 | + |
| | Skor pengabdian (F7) | 0,20 | + |

**Skor tiap dimensi** = rata-rata berbobot z-score bertanda arah:

$$S_{\text{dim}} = \frac{\sum_f (\text{bobot}_f \cdot \text{arah}_f \cdot z_f)}{\sum_f \text{bobot}_f}$$

**Skor komposit** = gabungan dua dimensi menurut bobot dimensi (di sini 50:50):

$$S_{\text{komposit}} = w_{\text{aka}} \cdot S_{\text{akademik}} + w_{\text{non}} \cdot S_{\text{non-akademik}}$$

Catatan penting soal **arah**: konsistensi memakai arah **−1** karena nilainya adalah
standar deviasi IPK — makin kecil justru makin stabil (baik). Fitur lain arah +1
(makin tinggi makin baik).

## 5. Penentuan label & deskripsi

1. Hitung `S_komposit` tiap klaster, **urutkan menurun** (peringkat 0 = tertinggi).
2. Beri nama dari **katalog** menurut peringkat:
   `["Berprestasi", "Menengah", "Perlu Bimbingan"]` (peringkat 0 → "Berprestasi",
   terendah → "Perlu Bimbingan", tengah → "Menengah").
3. Tambahkan **deskripsi kualitatif** dari dua sub-dimensi (akademik & non-akademik,
   masing-masing digolongkan tinggi / sedang / rendah pada ambang z = ±0,25),
   mis. *"Unggul akademik, tetapi minim prestasi/kegiatan non-akademik."*

Katalog nama, bobot, dan arah semuanya **terkonfigurasi** (`KONFIG_LABEL_DEFAULT` di
`ml/pipeline/pelabelan.py`) sehingga mudah ditinjau pimpinan dan — ke depan —
dikelola lewat CRUD "Kategori Klaster" di Laravel tanpa mengubah kode.

## 6. Contoh perhitungan (data ilustrasi, k = 3)

> ⚠️ Data **sintetik** untuk memperagakan logika; **bukan** hasil penelitian.

Tiga kelompok sengaja dibuat "menyimpang" antara IPK dan keaktifan:

| Label hasil | n | IPK rata | Skor prestasi | S akademik | S non-akademik | **S komposit** | Ringkasan |
|---|---|---|---|---|---|---|---|
| **Berprestasi** | 40 | 3,09 | 113 | 0,04 | 1,39 | **0,72** | Aktif & berprestasi non-akademik; akademik menengah |
| **Menengah** | 40 | **3,77** | 4 | 1,18 | −0,71 | **0,24** | Unggul akademik, tetapi minim prestasi/kegiatan non-akademik |
| **Perlu Bimbingan** | 40 | 2,49 | 4 | −1,22 | −0,69 | **−0,96** | Perlu perhatian menyeluruh |

**Poin kunci:** kelompok ber-IPK **tertinggi (3,77)** tetapi tidak aktif justru
tergolong **"Menengah"**, bukan "Berprestasi". Kelompok ber-IPK sedang (3,09) namun
aktif & banyak prestasi menempati peringkat teratas. Inilah perilaku multi-fitur
yang diminta: label tidak lagi ditarik oleh IPK semata.

## 7. Kejujuran & keterbatasan (untuk BAB III/IV)

- Skor & label adalah **aturan interpretasi transparan**, bukan klaim ilmiah. Sumber
  karakteristik utama tetap **centroid tiap fitur** (ditampilkan di dashboard).
- Bobot 40/30/20/10 dan 50:50 antar-dimensi adalah **nilai awal yang dapat ditinjau**
  bersama pembimbing/WD III; bukan angka baku. Sensitivitas terhadap bobot sebaiknya
  didiskusikan di BAB IV.
- Pelabelan tidak mengubah keanggotaan klaster (itu murni hasil K-Means); ia hanya
  memberi nama & urutan pada klaster yang sudah terbentuk.

## 8. Rencana lanjutan (CRUD Kategori Klaster)

Agar WD III dapat menyesuaikan katalog nama, bobot, arah, dan rekomendasi pembinaan
tanpa menyentuh kode, konfigurasi ini disiapkan untuk dipindahkan ke master
**Kategori Klaster** di Laravel (dikirim ke service Python lewat parameter
`konfigurasi_label` yang sudah tersedia di `interpret_clusters` /
`jalankan_klasterisasi`). Belum diimplementasikan — menunggu konfirmasi daftar label
final dari dosen.
